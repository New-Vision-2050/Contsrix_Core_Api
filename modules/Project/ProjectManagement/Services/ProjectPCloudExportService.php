<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
use Modules\Project\ProjectManagement\Exceptions\PCloudConfigurationException;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Shared\PCloud\Services\PCloudClient;
use Modules\Shared\PCloud\Services\PCloudArchiveSyncService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ProjectPCloudExportService
{
    private const MAINTENANCE_FOLDER = 'الصيانة والطوارئ';

    private const PROJECTS_FOLDER = 'المشاريع';

    public function __construct(
        private readonly PCloudClient $client,
        private readonly PCloudArchiveSyncService $archiveSyncService,
    ) {}

    public function ensureConfigured(): void
    {
        if (! (bool) config('pcloud.enabled')) {
            throw new PCloudConfigurationException('pCloud integration is disabled.');
        }

        foreach (['email', 'password', 'root_folder'] as $key) {
            if (! is_string(config("pcloud.{$key}")) || trim((string) config("pcloud.{$key}")) === '') {
                throw new PCloudConfigurationException("pCloud {$key} is not configured.");
            }
        }
    }

    public function dispatchMode(): string
    {
        return strtolower((string) config('pcloud.dispatch', 'queue'));
    }

    public function targetPath(ProjectManagement $project): string
    {
        return implode('/', [
            $this->rootFolderName(),
            $this->normalizePathPart($this->companyFolderName($project)),
            self::PROJECTS_FOLDER,
            $this->normalizePathPart((string) $project->name),
            self::MAINTENANCE_FOLDER,
        ]);
    }

    /**
     * @return array{run_id:string, project_id:string, folders_created_or_found:int, files_uploaded:int, files_skipped:int, files_failed:int, path:string}
     */
    public function export(ProjectManagement $project, string $runId): array
    {
        $this->ensureConfigured();

        $foldersCreatedOrFound = 0;
        $filesUploaded = 0;
        $filesSkipped = 0;
        $filesFailed = 0;

        $rootFolder = $this->client->ensureFolder(0, $this->rootFolderName());
        $foldersCreatedOrFound++;

        $companyFolderId = $this->client->ensureFolder($rootFolder, $this->companyFolderName($project));
        $foldersCreatedOrFound++;

        $projectsFolderId = $this->client->ensureFolder($companyFolderId, self::PROJECTS_FOLDER);
        $foldersCreatedOrFound++;

        $projectFolderId = $this->client->ensureFolder($projectsFolderId, (string) $project->name);
        $foldersCreatedOrFound++;

        $maintenanceFolderId = $this->client->ensureFolder($projectFolderId, self::MAINTENANCE_FOLDER);
        $foldersCreatedOrFound++;

        foreach ($this->notificationsForProject($project) as $notification) {
            $notificationFolderId = $this->client->ensureFolder(
                $maintenanceFolderId,
                (string) ($notification->notification_number ?: $notification->id),
            );
            $foldersCreatedOrFound++;

            foreach ($this->collectMedia($notification) as $media) {
                try {
                    if ($this->uploadMedia($notificationFolderId, $media)) {
                        $filesUploaded++;
                    } else {
                        $filesSkipped++;
                    }
                } catch (Throwable $exception) {
                    $filesFailed++;

                    Log::warning('PCloud media export failed', [
                        'run_id' => $runId,
                        'project_id' => $project->id,
                        'notification_id' => $notification->id,
                        'media_id' => $media->id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        }

        foreach ($this->archiveMediaForProject($project) as $media) {
            try {
                if ($this->archiveSyncService->syncMedia($media) === 'uploaded') {
                    $filesUploaded++;
                } else {
                    $filesSkipped++;
                }
            } catch (Throwable $exception) {
                $filesFailed++;

                Log::warning('PCloud project archive media export failed', [
                    'run_id' => $runId,
                    'project_id' => $project->id,
                    'media_id' => $media->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return [
            'run_id' => $runId,
            'project_id' => (string) $project->id,
            'folders_created_or_found' => $foldersCreatedOrFound,
            'files_uploaded' => $filesUploaded,
            'files_skipped' => $filesSkipped,
            'files_failed' => $filesFailed,
            'path' => $this->targetPath($project),
        ];
    }

    /**
     * @return Collection<int, ProjectNotification>
     */
    private function notificationsForProject(ProjectManagement $project): Collection
    {
        return ProjectNotification::query()
            ->where('company_id', (string) $project->company_id)
            ->where('project_id', (string) $project->id)
            ->with([
                'media',
                'employeeTask.media',
                'employeeTask.approvalRequests.media',
                'employeeTask.siteStatusUpdates.media',
                'employeeTask.fines.media',
                'employeeTask.workStoppageReports.media',
                'employeeTask.workResumptions.media',
                'siteStatusUpdates.media',
                'fines.media',
                'workStoppageReports.media',
                'workResumptions.media',
            ])
            ->orderBy('notification_number')
            ->get();
    }

    /**
     * @return Collection<int, Media>
     */
    private function collectMedia(ProjectNotification $notification): Collection
    {
        $items = collect();

        foreach ([
            'attachments',
            'site_status_update_attachments',
            'fine_attachments',
            'work_stoppage_report_attachments',
            'work_resumption_attachments',
            'update_attachments',
        ] as $collectionName) {
            $this->pushMedia($items, $notification->getMedia($collectionName));
        }

        $task = $notification->employeeTask;
        if ($task !== null) {
            $this->pushMedia($items, $task->getMedia('attachments'));

            foreach ($task->approvalRequests as $approvalRequest) {
                $this->pushMedia($items, $approvalRequest->getMedia('attachments'));
            }

            foreach ([
                $task->siteStatusUpdates,
                $task->fines,
                $task->workStoppageReports,
                $task->workResumptions,
            ] as $records) {
                foreach ($records as $record) {
                    $this->pushMedia($items, $record->getMedia('attachments'));
                }
            }
        }

        foreach ([
            $notification->siteStatusUpdates,
            $notification->fines,
            $notification->workStoppageReports,
            $notification->workResumptions,
        ] as $records) {
            foreach ($records as $record) {
                $this->pushMedia($items, $record->getMedia('attachments'));
            }
        }

        return $items
            ->unique(static fn (Media $media) => (string) $media->id)
            ->values();
    }

    /**
     * @param  Collection<int, Media>  $items
     * @param  iterable<int, Media>  $mediaItems
     */
    private function pushMedia(Collection $items, iterable $mediaItems): void
    {
        foreach ($mediaItems as $media) {
            if ($media instanceof Media) {
                $items->push($media);
            }
        }
    }

    /**
     * @return bool True when uploaded; false when the file already exists remotely.
     */
    private function uploadMedia(int $folderId, Media $media): bool
    {
        $disk = $media->disk ?: $media->conversions_disk ?: (string) config('media-library.disk_name', 'public');
        $path = $media->getPathRelativeToRoot();

        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Media file does not exist on configured disk.');
        }

        $contents = Storage::disk($disk)->get($path);
        if ($contents === '') {
            throw new \RuntimeException('Media file is empty.');
        }

        $result = $this->client->uploadFile(
            $folderId,
            $this->mediaFileName($media),
            $contents,
            $media->mime_type ?: null,
        );

        return ! (bool) ($result['skipped'] ?? false);
    }

    /**
     * ArchiveLibrary contains the copies created when an Attachment Request is
     * approved. Syncing these rows makes the manual project endpoint a real
     * backfill for existing project documents, not only project notifications.
     *
     * @return Collection<int, Media>
     */
    private function archiveMediaForProject(ProjectManagement $project): Collection
    {
        return ArchiveFile::query()
            ->where('company_id', (string) $project->company_id)
            ->where('project_id', (string) $project->id)
            ->with('media')
            ->get()
            ->flatMap(static fn (ArchiveFile $file): Collection => $file->media)
            ->unique(static fn (Media $media): string => (string) $media->id)
            ->values();
    }

    private function mediaFileName(Media $media): string
    {
        $name = basename((string) ($media->file_name ?: $media->name ?: 'attachment'));

        return $name !== '' ? $name : 'attachment';
    }

    private function companyFolderName(ProjectManagement $project): string
    {
        $company = $project->company;

        if ($company !== null && method_exists($company, 'getTranslation')) {
            foreach (array_unique([app()->getLocale(), 'ar', 'en']) as $locale) {
                $name = trim($company->getTranslation('name', $locale));

                if ($name !== '') {
                    return $name;
                }
            }
        }

        $name = $company?->getRawOriginal('name');
        if (is_string($name) && trim($name) !== '') {
            return $name;
        }

        return (string) $project->company_id;
    }

    private function rootFolderName(): string
    {
        return $this->normalizePathPart((string) config('pcloud.root_folder', 'Constrix Archive'));
    }

    private function normalizePathPart(string $name): string
    {
        $name = str_replace(["\0", '/', '\\'], ['', '-', '-'], $name);
        $name = trim($name);
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;
        $name = trim($name);

        return $name !== '' ? $name : 'untitled';
    }
}
