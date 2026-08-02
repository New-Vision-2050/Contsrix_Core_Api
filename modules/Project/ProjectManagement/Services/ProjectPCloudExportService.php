<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class ProjectPCloudExportService
{
    private const MAINTENANCE_FOLDER = 'الصيانة والطوارئ';

    public function __construct(
        private readonly PCloudClient $client,
    ) {}

    public function ensureConfigured(): void
    {
        $this->client->assertConfigured();
    }

    public function dispatchMode(): string
    {
        return strtolower((string) config('services.pcloud.dispatch', 'sync'));
    }

    public function targetPath(ProjectManagement $project): string
    {
        return implode('/', [
            $this->client->rootFolderName(),
            $this->client->normalizeFolderName((string) $project->name),
            self::MAINTENANCE_FOLDER,
        ]);
    }

    /**
     * @return array{run_id:string, project_id:string, folders_created_or_found:int, files_uploaded:int, files_failed:int, path:string}
     */
    public function export(ProjectManagement $project, string $runId): array
    {
        $this->ensureConfigured();

        $foldersCreatedOrFound = 0;
        $filesUploaded = 0;
        $filesFailed = 0;

        $rootFolder = $this->client->ensureFolder(0, $this->client->rootFolderName());
        $foldersCreatedOrFound++;

        $projectFolder = $this->client->ensureFolder($rootFolder['folderid'], (string) $project->name);
        $foldersCreatedOrFound++;

        $maintenanceFolder = $this->client->ensureFolder($projectFolder['folderid'], self::MAINTENANCE_FOLDER);
        $foldersCreatedOrFound++;

        foreach ($this->notificationsForProject($project) as $notification) {
            $notificationFolder = $this->client->ensureFolder(
                $maintenanceFolder['folderid'],
                (string) ($notification->notification_number ?: $notification->id),
            );
            $foldersCreatedOrFound++;

            foreach ($this->collectMedia($notification) as $media) {
                try {
                    $this->uploadMedia($notificationFolder['folderid'], $media);
                    $filesUploaded++;
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

        return [
            'run_id' => $runId,
            'project_id' => (string) $project->id,
            'folders_created_or_found' => $foldersCreatedOrFound,
            'files_uploaded' => $filesUploaded,
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

    private function uploadMedia(int $folderId, Media $media): void
    {
        $disk = $media->disk ?: $media->conversions_disk ?: (string) config('media-library.disk_name', 'public');
        $path = $media->getPathRelativeToRoot();

        if (! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Media file does not exist on configured disk.');
        }

        $stream = Storage::disk($disk)->readStream($path);
        if (! is_resource($stream)) {
            throw new \RuntimeException('Unable to open media file stream.');
        }

        try {
            $this->client->uploadFile(
                folderId: $folderId,
                stream: $stream,
                filename: $this->mediaFileName($media),
                mtime: $media->updated_at?->timestamp,
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    private function mediaFileName(Media $media): string
    {
        $name = basename((string) ($media->file_name ?: $media->name ?: 'attachment'));

        return $name !== '' ? $name : 'attachment';
    }
}
