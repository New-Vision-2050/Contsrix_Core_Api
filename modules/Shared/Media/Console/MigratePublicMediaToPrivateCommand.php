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
        {--delete-source : Also delete the old public-bucket copy after it is verified on the private bucket. Without this flag, --apply only copies + repoints the DB, leaving the old public file intact as a safety net.}
        {--cleanup-orphans : Instead of migrating, sweep leftover public-bucket copies for rows already migrated (disk already s3_private). Run this as a final step after verifying the app works correctly on private storage.}
        {--chunk=100 : Number of media rows to process per chunk.}
        {--only= : Comma-separated list of fully-qualified model class names to restrict this run to (for controlled testing).}
        {--limit=0 : Stop after migrating this many rows in total (0 = no limit). Useful for a small first test batch.}';

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
            'upload_user',
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
        'Modules\EmployeeTask\Models\EmployeeTaskRequest' => ['attachments', 'end_attachments'],
        'Modules\ClientRequest\Models\ClientRequest' => ['attachments'],
        'Modules\ArchiveLibrary\File\Models\File' => ['upload'],
        'Modules\Reports\Models\Report' => ['report_file'],
        'Modules\Project\ProjectType\Models\SafetyWeeklyReport' => ['weekly_report_file'],
        'Modules\Project\ProjectType\Models\SafetyRecord' => ['violation_evidence'],
        'Modules\UserInfo\JobOffer\Models\JobOffer' => ['upload_offerjob'],
        'Modules\Project\ProjectManagement\Models\ProjectManagement' => ['stamp'],
        'Modules\Project\ProjectManagement\Models\ProjectNotification' => [
            'attachments',
            'update_attachments',
            'site_status_update_attachments',
            'fine_attachments',
            'work_stoppage_report_attachments',
            'work_resumption_attachments',
        ],
        'Modules\Project\ProjectManagement\Models\ProjectNotificationSiteStatusUpdate' => ['attachments'],
        'Modules\Project\ProjectManagement\Models\ProjectNotificationWorkStoppageReport' => ['attachments'],
        'Modules\Project\ProjectManagement\Models\ProjectNotificationWorkResumption' => ['attachments'],
        'Modules\Project\ProjectManagement\Models\ProjectNotificationFine' => ['attachments'],
    ];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $deleteSource = (bool) $this->option('delete-source');
        $chunkSize = (int) $this->option('chunk');
        $limit = (int) $this->option('limit');
        $onlyOption = $this->option('only');
        $only = $onlyOption ? array_map('trim', explode(',', (string) $onlyOption)) : null;

        if ((bool) $this->option('cleanup-orphans')) {
            return $this->cleanupOrphans($apply, $chunkSize, $limit, $only);
        }

        if (! $apply) {
            $this->warn('DRY RUN — no files will be moved and no DB rows changed. Pass --apply to execute.');
        } elseif (! $deleteSource) {
            $this->warn('--apply without --delete-source: old public copies will be KEPT as a safety net. Copy + DB repoint only.');
        } else {
            $this->warn('--apply WITH --delete-source: old public copies will be permanently removed after verification.');
        }

        if ($limit > 0) {
            $this->comment("Limiting this run to {$limit} migrated row(s).");
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
        $stop = false;

        foreach (self::TARGETS as $modelType => $collections) {
            if ($stop) {
                break;
            }

            if ($only !== null && ! in_array($modelType, $only, true)) {
                continue;
            }

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

            $query->chunkById($chunkSize, function ($mediaItems) use (&$totalMigrated, &$totalFailed, &$totalSkippedMissing, &$stop, $apply, $deleteSource, $limit) {
                /** @var Media $media */
                foreach ($mediaItems as $media) {
                    if ($limit > 0 && $totalMigrated >= $limit) {
                        $stop = true;

                        return false; // stop chunking
                    }

                    $result = $this->migrateOne($media, $apply, $deleteSource);

                    match ($result) {
                        'migrated' => $totalMigrated++,
                        'missing' => $totalSkippedMissing++,
                        default => $totalFailed++,
                    };
                }

                return true;
            });
        }

        // Folder module: only migrate files belonging to folders with access_type = 'private'.
        if (! $stop && ($only === null || in_array('Modules\ArchiveLibrary\Folder\Models\Folder', $only, true))) {
            $this->migratePrivateFolders($apply, $deleteSource, $chunkSize, $limit, $totalMatched, $totalMigrated, $totalFailed, $totalSkippedMissing);
        }

        $this->newLine();
        $this->info("Matched: {$totalMatched} | Migrated: {$totalMigrated} | Missing source file: {$totalSkippedMissing} | Failed: {$totalFailed}");

        if (! $apply) {
            $this->comment('Re-run with --apply to perform the migration.');
        }

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migratePrivateFolders(bool $apply, bool $deleteSource, int $chunkSize, int $limit, int &$totalMatched, int &$totalMigrated, int &$totalFailed, int &$totalSkippedMissing): void
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

        $query->chunkById($chunkSize, function ($mediaItems) use (&$totalMigrated, &$totalFailed, &$totalSkippedMissing, $apply, $deleteSource, $limit) {
            foreach ($mediaItems as $media) {
                if ($limit > 0 && $totalMigrated >= $limit) {
                    return false;
                }

                $result = $this->migrateOne($media, $apply, $deleteSource);

                match ($result) {
                    'migrated' => $totalMigrated++,
                    'missing' => $totalSkippedMissing++,
                    default => $totalFailed++,
                };
            }

            return true;
        });
    }

    /**
     * @return 'migrated'|'missing'|'failed'
     */
    private function migrateOne(Media $media, bool $apply, bool $deleteSource): string
    {
        $relativePath = $media->getPathRelativeToRoot();

        try {
            if (! $this->diskFileExists('s3_public', $relativePath)) {
                $this->warn("  [missing] {$media->id} {$relativePath}");

                return 'missing';
            }

            if (! $apply) {
                $this->line("  [dry-run] would migrate {$media->id} {$relativePath}");

                return 'migrated';
            }

            // 1. Copy to the private bucket first. The public original is
            //    left untouched at this point no matter what happens next.
            if (! $this->diskFileExists('s3_private', $relativePath)) {
                $contents = Storage::disk('s3_public')->get($relativePath);
                Storage::disk('s3_private')->put($relativePath, $contents);
            }

            // 2. Verify the copy landed correctly (byte-size match) before
            //    touching anything else. If this fails, nothing else happens.
            $publicSize = Storage::disk('s3_public')->size($relativePath);
            $privateSize = Storage::disk('s3_private')->size($relativePath);

            if ($publicSize !== $privateSize) {
                $this->error("  [error] {$media->id} {$relativePath}: size mismatch after copy (public={$publicSize}, private={$privateSize}). Public copy left intact.");

                return 'failed';
            }

            // 3. Only now repoint the DB row to the verified private copy.
            $media->forceFill([
                'disk' => 's3_private',
                'conversions_disk' => 's3_private',
            ])->save();

            // 4. Only delete the old public object if explicitly requested,
            //    and only after the DB repoint above succeeded.
            if ($deleteSource) {
                Storage::disk('s3_public')->delete($relativePath);
                $this->line("  [ok] migrated + deleted public copy: {$media->id} {$relativePath}");
            } else {
                $this->line("  [ok] migrated (public copy kept): {$media->id} {$relativePath}");
            }

            return 'migrated';
        } catch (\Throwable $e) {
            $this->error("  [error] {$media->id} {$relativePath}: " . $this->fullExceptionMessage($e));

            return 'failed';
        }
    }

    /**
     * Safe existence check that avoids Storage::exists()/Flysystem's
     * directoryExists() fallback, which throws UnableToCheckDirectoryExistence
     * on DigitalOcean Spaces for nonexistent nested paths instead of
     * returning false. size() maps to a direct HeadObject call instead.
     */
    private function diskFileExists(string $disk, string $path): bool
    {
        try {
            Storage::disk($disk)->size($path);

            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function fullExceptionMessage(\Throwable $e): string
    {
        $parts = [$e->getMessage()];
        $previous = $e->getPrevious();

        while ($previous !== null) {
            $parts[] = get_class($previous) . ': ' . $previous->getMessage();
            $previous = $previous->getPrevious();
        }

        return implode(' <= caused by: ', $parts);
    }

    /**
     * Sweep leftover public-bucket copies for media rows that are ALREADY
     * migrated (disk = s3_private), left there intentionally by a prior
     * --apply run without --delete-source. Only deletes the public copy
     * after confirming the private copy exists and matches in size.
     */
    private function cleanupOrphans(bool $apply, int $chunkSize, int $limit, ?array $only): int
    {
        if (! $apply) {
            $this->warn('DRY RUN — no files will be deleted. Pass --apply to execute.');
        } else {
            $this->warn('--apply: leftover public copies of already-migrated rows will be permanently deleted.');
        }

        $totalMatched = 0;
        $totalCleaned = 0;
        $totalFailed = 0;
        $totalSkippedMissing = 0;

        foreach (self::TARGETS as $modelType => $collections) {
            if ($only !== null && ! in_array($modelType, $only, true)) {
                continue;
            }

            if (! class_exists($modelType)) {
                continue;
            }

            $query = Media::query()
                ->where('model_type', $modelType)
                ->where('disk', 's3_private')
                ->whereIn('collection_name', $collections);

            $count = $query->count();
            $totalMatched += $count;

            if ($count === 0) {
                continue;
            }

            $this->line("Model {$modelType} [".implode(',', $collections)."]: {$count} already-migrated row(s) to check for orphaned public copies.");

            $query->chunkById($chunkSize, function ($mediaItems) use (&$totalCleaned, &$totalFailed, &$totalSkippedMissing, $apply, $limit) {
                foreach ($mediaItems as $media) {
                    if ($limit > 0 && $totalCleaned >= $limit) {
                        return false;
                    }

                    $result = $this->cleanupOne($media, $apply);

                    match ($result) {
                        'cleaned' => $totalCleaned++,
                        'missing' => $totalSkippedMissing++,
                        default => $totalFailed++,
                    };
                }

                return true;
            });
        }

        $folderModel = 'Modules\ArchiveLibrary\Folder\Models\Folder';

        if (($only === null || in_array($folderModel, $only, true)) && class_exists($folderModel)) {
            $privateFolderIds = $folderModel::query()->where('access_type', 'private')->pluck('id');

            if ($privateFolderIds->isNotEmpty()) {
                $query = Media::query()
                    ->where('model_type', $folderModel)
                    ->where('disk', 's3_private')
                    ->where('collection_name', 'upload')
                    ->whereIn('model_id', $privateFolderIds);

                $count = $query->count();
                $totalMatched += $count;

                if ($count > 0) {
                    $this->line("Model {$folderModel} [upload] (private folders): {$count} already-migrated row(s) to check.");

                    $query->chunkById($chunkSize, function ($mediaItems) use (&$totalCleaned, &$totalFailed, &$totalSkippedMissing, $apply, $limit) {
                        foreach ($mediaItems as $media) {
                            if ($limit > 0 && $totalCleaned >= $limit) {
                                return false;
                            }

                            $result = $this->cleanupOne($media, $apply);

                            match ($result) {
                                'cleaned' => $totalCleaned++,
                                'missing' => $totalSkippedMissing++,
                                default => $totalFailed++,
                            };
                        }

                        return true;
                    });
                }
            }
        }

        $this->newLine();
        $this->info("Checked: {$totalMatched} | Cleaned: {$totalCleaned} | No orphan found: {$totalSkippedMissing} | Failed: {$totalFailed}");

        if (! $apply) {
            $this->comment('Re-run with --apply to actually delete orphaned public copies.');
        }

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * @return 'cleaned'|'missing'|'failed'
     */
    private function cleanupOne(Media $media, bool $apply): string
    {
        $relativePath = $media->getPathRelativeToRoot();

        try {
            if (! $this->diskFileExists('s3_public', $relativePath)) {
                // Nothing left to clean up — already gone or never existed there.
                return 'missing';
            }

            if (! $this->diskFileExists('s3_private', $relativePath)) {
                $this->error("  [skip] {$media->id} {$relativePath}: private copy missing, refusing to delete public original.");

                return 'failed';
            }

            $publicSize = Storage::disk('s3_public')->size($relativePath);
            $privateSize = Storage::disk('s3_private')->size($relativePath);

            if ($publicSize !== $privateSize) {
                $this->error("  [skip] {$media->id} {$relativePath}: size mismatch (public={$publicSize}, private={$privateSize}), refusing to delete.");

                return 'failed';
            }

            if (! $apply) {
                $this->line("  [dry-run] would delete orphaned public copy: {$media->id} {$relativePath}");

                return 'cleaned';
            }

            Storage::disk('s3_public')->delete($relativePath);
            $this->line("  [ok] deleted orphaned public copy: {$media->id} {$relativePath}");

            return 'cleaned';
        } catch (\Throwable $e) {
            $this->error("  [error] {$media->id} {$relativePath}: " . $this->fullExceptionMessage($e));

            return 'failed';
        }
    }
}
