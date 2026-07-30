<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
use Modules\ArchiveLibrary\Folder\Models\Folder as ArchiveFolder;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\PCloud\Jobs\SyncMediaToPCloudJob;
use Throwable;

class PCloudArchiveSyncService
{
    public function __construct(
        private readonly PCloudClient $client,
    ) {
    }

    public function shouldSync(CustomMedia $media): bool
    {
        if (!$this->client->isConfigured()) {
            return false;
        }

        // Direct Archive Library uploads
        if (in_array($media->model_type, [
            ArchiveFile::class,
            ArchiveFolder::class,
        ], true)) {
            return true;
        }

        // Project notifications (site status updates, etc.) create Archive File /
        // Folder rows, then attach media to the notification model with file_id /
        // folder_id linking back to Archive Library.
        if ($this->resolveArchiveFileId($media) || $this->resolveArchiveFolderId($media)) {
            return true;
        }

        $filePath = (string) ($media->getCustomProperty('file_path') ?? '');

        return str_starts_with($filePath, 'project-notifications/');
    }

    public function dispatchSync(CustomMedia $media): void
    {
        if (!$this->client->isConfigured()) {
            Log::debug('pCloud sync skipped: not configured', [
                'media_id' => $media->id,
                'enabled' => (bool) config('pcloud.enabled'),
                'email_set' => filled(config('pcloud.email')),
            ]);

            return;
        }

        if (!$this->shouldSync($media)) {
            Log::debug('pCloud sync skipped: media not archive-linked', [
                'media_id' => $media->id,
                'model_type' => $media->model_type,
                'file_id' => $this->resolveArchiveFileId($media),
                'folder_id' => $this->resolveArchiveFolderId($media),
            ]);

            return;
        }

        // afterResponse runs in-process after the HTTP response (no queue worker
        // required). Production still has a worker for other jobs.
        SyncMediaToPCloudJob::dispatch(
            (string) $media->id,
            tenant('id') ? (string) tenant('id') : null,
        )->afterResponse();

        Log::info('pCloud sync dispatched', [
            'media_id' => $media->id,
            'model_type' => $media->model_type,
            'file_id' => $this->resolveArchiveFileId($media),
            'folder_id' => $this->resolveArchiveFolderId($media),
        ]);
    }

    public function syncMedia(CustomMedia $media): void
    {
        if (!$this->shouldSync($media)) {
            return;
        }

        $contents = $this->readMediaContents($media);
        if ($contents === null || $contents === '') {
            Log::warning('pCloud sync skipped: empty media contents', [
                'media_id' => $media->id,
            ]);

            return;
        }

        $remoteFolderPath = $this->buildRemoteFolderPath($media);
        $folderId = $this->client->ensureFolderPath($remoteFolderPath);
        $filename = $media->file_name ?: ($media->name ?: 'file');

        $result = $this->client->uploadFile($folderId, $filename, $contents);

        Log::info('pCloud archive sync uploaded file', [
            'media_id' => $media->id,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'remote_folder' => $remoteFolderPath,
            'filename' => $filename,
            'fileid' => $result['metadata'][0]['fileid'] ?? ($result['fileids'][0] ?? null),
        ]);
    }

    private function buildRemoteFolderPath(CustomMedia $media): string
    {
        $root = trim((string) config('pcloud.root_folder', 'Constrix Archive'), '/');
        $tenantSegment = $this->resolveTenantSegment($media);
        $archivePath = $this->resolveArchivePath($media);

        return collect([$root, $tenantSegment, $archivePath])
            ->filter(static fn (?string $part): bool => filled($part))
            ->implode('/');
    }

    private function resolveTenantSegment(CustomMedia $media): string
    {
        $fileId = $this->resolveArchiveFileId($media);
        if ($fileId) {
            $file = ArchiveFile::query()->withoutTenancy()->find($fileId);
            if ($file?->company_id) {
                return (string) $file->company_id;
            }
        }

        $folderId = $this->resolveArchiveFolderId($media);
        if ($folderId) {
            $folder = ArchiveFolder::query()->withoutTenancy()->find($folderId);
            if ($folder?->company_id) {
                return (string) $folder->company_id;
            }
        }

        $model = $media->model;
        if ($model instanceof ArchiveFile || $model instanceof ArchiveFolder) {
            if (!empty($model->company_id)) {
                return (string) $model->company_id;
            }
        }

        if (is_object($model) && isset($model->company_id) && filled($model->company_id)) {
            return (string) $model->company_id;
        }

        if (tenant('id')) {
            return (string) tenant('id');
        }

        return 'shared';
    }

    private function resolveArchivePath(CustomMedia $media): string
    {
        // Prefer real Archive Library folder tree (project → emergency → …)
        $folderId = $this->resolveArchiveFolderId($media);
        if ($folderId) {
            $folder = ArchiveFolder::query()->withoutTenancy()->find($folderId);
            if ($folder) {
                return $this->folderHierarchyPath($folder);
            }
        }

        $fileId = $this->resolveArchiveFileId($media);
        if ($fileId) {
            $file = ArchiveFile::query()->withoutTenancy()->with('folder')->find($fileId);
            if ($file?->folder) {
                return $this->folderHierarchyPath($file->folder);
            }
        }

        $customPath = $media->getCustomProperty('file_path');
        if (is_string($customPath) && $customPath !== '' && $customPath !== 'default' && $customPath !== 'default_path') {
            return trim($customPath, '/');
        }

        $model = $media->model;

        if ($model instanceof ArchiveFolder) {
            return $this->folderHierarchyPath($model);
        }

        if ($model instanceof ArchiveFile) {
            if ($model->folder) {
                return $this->folderHierarchyPath($model->folder);
            }

            return 'files';
        }

        return 'files';
    }

    private function resolveArchiveFileId(CustomMedia $media): ?string
    {
        $fileId = $media->file_id ?: $media->getCustomProperty('file_id');

        return filled($fileId) ? (string) $fileId : null;
    }

    private function resolveArchiveFolderId(CustomMedia $media): ?string
    {
        $folderId = $media->folder_id ?: $media->getCustomProperty('folder_id');

        return filled($folderId) ? (string) $folderId : null;
    }

    private function folderHierarchyPath(ArchiveFolder $folder): string
    {
        $parts = [$folder->name];
        $current = $folder;

        while ($current->parent_id) {
            $parent = ArchiveFolder::query()->withoutTenancy()->find($current->parent_id);
            if (!$parent) {
                break;
            }
            array_unshift($parts, $parent->name);
            $current = $parent;
        }

        return implode('/', array_filter($parts));
    }

    private function readMediaContents(CustomMedia $media): ?string
    {
        try {
            $disk = $media->disk ?: 'public';
            $relativePath = method_exists($media, 'getPathRelativeToRoot')
                ? $media->getPathRelativeToRoot()
                : $media->getPath();

            if ($relativePath && Storage::disk($disk)->exists($relativePath)) {
                return Storage::disk($disk)->get($relativePath);
            }

            $absolutePath = $media->getPath();
            if (is_string($absolutePath) && is_file($absolutePath)) {
                return file_get_contents($absolutePath) ?: null;
            }

            $url = $media->getFullUrl();
            if (is_string($url) && $url !== '') {
                if (!str_starts_with($url, 'http')) {
                    $url = 'https://' . ltrim($url, '/');
                }

                $response = \Illuminate\Support\Facades\Http::timeout(60)->get($url);
                if ($response->successful()) {
                    return $response->body();
                }
            }
        } catch (Throwable $e) {
            Log::error('pCloud failed to read media contents', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
