<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One-off remediation command for the constrix.fra1.digitaloceanspaces.com
 * public data exposure incident (Sep 2026).
 *
 * Moves Media records for known-sensitive model/collection pairs from the
 * public disk (s3_public) to the private disk (s3_private), physically
 * copying the object, deleting the public copy, and updating the media
 * row's disk/conversions_disk columns.
 *
 * Dry-run by default. Pass --apply to actually perform the migration.
 */
class MigratePublicMediaToPrivateCommand extends Command
{
    protected $signature = 'media:migrate-to-private
        {--apply : Actually perform the migration. Without this flag, only a dry-run report is printed.}
        {--chunk=100 : Number of media rows to process per chunk.}';

    protected $description = 'Migrate sensitive media currently stored on the public disk to the private disk (post-breach remediation)';

    /**
     * [model_type => collection_name(s)]. '*' means all collections for that model.
     *
     * @var array<string, string[]|string>
     */
    private const TARGETS = [
        'Modules\Project\ProjectManagement\Models\AttachmentRequestItem' => ['attachments'],
        'Modules\Company\CompanyCore\Models\CompanyOfficialDocument' => ['upload'],
        'Modules\Company\CompanyCore\Models\CompanyLegalData' => ['upload'],
        'Modules\CompanyUser\Models\CompanyUser' => [
            'file_passport',
            'file_identity',
            'file_border_number',
            'file_entry_number',
            'file_work_permit',
            'file_industrial_safety',
            'upload_biography',
        ],
        'Modules\UserInfo\ProfessionalCertificate\Models\ProfessionalCertificate' => ['upload'],
        'Modules\UserInfo\Qualification\Models\Qualification' => ['upload_Qualification'],
        'Modules\UserInfo\UserEducationalCourse\Models\UserEducationalCourse' => ['upload'],
        'Modules\UserInfo\EmploymentContract\Models\EmploymentContract' => ['upload_employment_contracts'],
        'Modules\MedicalInsurance\Models\MedicalInsurance' => ['attachments'],
        'Modules\AdminRequest\Models\AdminRequest' => ['upload'],
        'Modules\EmployeeTask\Models\EmployeeTaskApprovalRequest' => ['attachments'],
        'Modules\EmployeeTask\Models\EmployeeTaskRequest' => ['attachments'],
        'Modules\ClientRequest\Models\ClientRequest' => ['attachments'],
        'Modules\ArchiveLibrary\File\Models\File' => ['upload'],
        'Modules\Reports\Models\Report' => ['report_file'],
        'Modules\Project\ProjectType\Models\SafetyWeeklyReport' => ['weekly_report_file'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $chunkSize = (int) $this->option('chunk');

        if (! $apply) {
            $this->warn('DRY RUN — no files will be moved and no DB rows changed. Pass --apply to execute.');
        }

        $publicBucket = config('filesystems.disks.s3_public.bucket');
        $privateBucket = config('filesystems.disks.s3_private.bucket');

        if (! is_string($publicBucket) || $publicBucket === '' || ! is_string($privateBucket) || $privateBucket === '') {
            $this->error('s3_public or s3_private disk bucket is not configured. Aborting.');

            return self::FAILURE;
        }

        $totalMatched = 0;
        $totalMigrated = 0;
        $totalFailed = 0;
        $totalSkippedMissing = 0;

        foreach (self::TARGETS as $modelType => $collections) {
            if (! class_exists($modelType)) {
                $this->warn("Skipping unknown model class: {$modelType}");

                continue;
            }

            $query = Media::query()
                ->where('model_type', $modelType)
                ->where('disk', 's3_public')
                ->whereIn('collection_name', $collections);

            // Folder collection needs the extra access_type check, handled separately below.
            $count = $query->count();
            $totalMatched += $count;

            $this->line("Model {$modelType} [".implode(',', $collections)."]: {$count} public media row(s) found.");

            $query->chunkById($chunkSize, function ($mediaItems) use (&$totalMigrated, &$totalFailed, &$totalSkippedMissing, $apply) {
                /** @var Media $media */
                foreach ($mediaItems as $media) {
                    $result = $this->migrateOne($media, $apply);

                    match ($result) {
                        'migrated' => $totalMigrated++,
                        'missing' => $totalSkippedMissing++,
                        default => $totalFailed++,
                    };
                }
            });
        }

        // Folder module: only migrate files belonging to folders with access_type = 'private'.
        $this->migratePrivateFolders($apply, $chunkSize, $totalMatched, $totalMigrated, $totalFailed, $totalSkippedMissing);

        $this->newLine();
        $this->info("Matched: {$totalMatched} | Migrated: {$totalMigrated} | Missing source file: {$totalSkippedMissing} | Failed: {$totalFailed}");

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the migration.');
        }

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migratePrivateFolders(bool $apply, int $chunkSize, int &$totalMatched, int &$totalMigrated, int &$totalFailed, int &$totalSkippedMissing): void
    {
        $folderModel = 'Modules\ArchiveLibrary\Folder\Models\Folder';

        if (! class_exists($folderModel)) {
            return;
        }

        $privateFolderIds = $folderModel::query()
            ->where('access_type', 'private')
            ->pluck('id');

        if ($privateFolderIds->isEmpty()) {
            return;
        }

        $query = Media::query()
            ->where('model_type', $folderModel)
            ->where('disk', 's3_public')
            ->where('collection_name', 'upload')
            ->whereIn('model_id', $privateFolderIds);

        $count = $query->count();
        $totalMatched += $count;

        $this->line("Model {$folderModel} [upload] (private folders only): {$count} public media row(s) found.");

        $query->chunkById($chunkSize, function ($mediaItems) use (&$totalMigrated, &$totalFailed, &$totalSkippedMissing, $apply) {
            foreach ($mediaItems as $media) {
                $result = $this->migrateOne($media, $apply);

                match ($result) {
                    'migrated' => $totalMigrated++,
                    'missing' => $totalSkippedMissing++,
                    default => $totalFailed++,
                };
            }
        });
    }

    /**
     * @return 'migrated'|'missing'|'failed'
     */
    private function migrateOne(Media $media, bool $apply): string
    {
        $relativePath = $media->getPathRelativeToRoot();

        try {
            if (! Storage::disk('s3_public')->exists($relativePath)) {
                $this->warn("  [missing] {$media->id} {$relativePath}");

                return 'missing';
            }

            if (! $apply) {
                $this->line("  [dry-run] would migrate {$media->id} {$relativePath}");

                return 'migrated';
            }

            $contents = Storage::disk('s3_public')->get($relativePath);
            Storage::disk('s3_private')->put($relativePath, $contents);
            Storage::disk('s3_public')->delete($relativePath);

            $media->forceFill([
                'disk' => 's3_private',
                'conversions_disk' => 's3_private',
            ])->save();

            $this->line("  [ok] migrated {$media->id} {$relativePath}");

            return 'migrated';
        } catch (\Throwable $e) {
            $this->error("  [error] {$media->id} {$relativePath}: {$e->getMessage()}");

            return 'failed';
        }
    }
}
