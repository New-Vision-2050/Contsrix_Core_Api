<?php

declare(strict_types=1);

namespace Modules\Shared\PCloud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Modules\ArchiveLibrary\File\Models\File as ArchiveFile;
use Modules\ArchiveLibrary\Folder\Models\Folder as ArchiveFolder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Shared\Media\Models\CustomMedia;
use Modules\Shared\PCloud\Jobs\SyncMediaToPCloudJob;
use Throwable;

class PCloudArchiveSyncService
{
    /** @var array<string, true> */
    private array $dispatchedMediaIds = [];

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
        $mediaId = (string) $media->id;
        if (isset($this->dispatchedMediaIds[$mediaId])) {
            return;
        }

        if (!$this->client->isConfigured()) {
            Log::warning('pCloud sync skipped: not configured', [
                'media_id' => $mediaId,
                'enabled' => (bool) config('pcloud.enabled'),
                'email_set' => filled(config('pcloud.email')),
            ]);

            return;
        }

        if (!$this->shouldSync($media)) {
            Log::info('pCloud sync skipped: media not archive-linked', [
                'media_id' => $mediaId,
                'model_type' => $media->model_type,
                'file_id' => $this->resolveArchiveFileId($media),
                'folder_id' => $this->resolveArchiveFolderId($media),
                'file_path' => $media->getCustomProperty('file_path'),
            ]);

            return;
        }

        $this->dispatchedMediaIds[$mediaId] = true;
        $companyId = tenant('id') ? (string) tenant('id') : null;
        $mode = strtolower((string) config('pcloud.dispatch', 'sync'));

        $run = function () use ($mediaId, $companyId, $mode, $media): void {
            Log::info('pCloud sync starting', [
                'media_id' => $mediaId,
                'mode' => $mode,
                'model_type' => $media->model_type,
                'file_id' => $this->resolveArchiveFileId($media),
                'folder_id' => $this->resolveArchiveFolderId($media),
            ]);

            if ($mode === 'queue') {
                SyncMediaToPCloudJob::dispatch($mediaId, $companyId);
                Log::info('pCloud sync job queued', ['media_id' => $mediaId]);

                return;
            }

            // Default: run now (works without queue worker / Octane terminating hooks)
            SyncMediaToPCloudJob::dispatchSync($mediaId, $companyId);
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($run);
        } else {
            $run();
        }
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
        $mimeType = $media->mime_type ?: null;

        $result = $this->client->uploadFile($folderId, $filename, $contents, $mimeType);

        Log::info('pCloud archive sync uploaded file', [
            'media_id' => $media->id,
            'model_type' => $media->model_type,
            'model_id' => $media->model_id,
            'remote_folder' => $remoteFolderPath,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'size' => strlen($contents),
            'fileid' => $result['metadata'][0]['fileid'] ?? ($result['fileids'][0] ?? null),
            'contenttype' => $result['metadata'][0]['contenttype'] ?? null,
        ]);
    }

    /**
     * Layout: Constrix Archive / {Company Name} / {Project Name} / {archive subfolders…}
     */
    private function buildRemoteFolderPath(CustomMedia $media): string
    {
        $context = $this->resolveArchiveContext($media);
        $root = trim((string) config('pcloud.root_folder', 'Constrix Archive'), '/');
        $companyName = $this->resolveCompanyName($context['company_id']);
        $projectName = $this->resolveProjectName($context['project_id']);
        $archivePath = $this->resolveArchivePath($media, $context, $projectName);

        return collect([$root, $companyName, $archivePath])
            ->filter(static fn (?string $part): bool => filled($part))
            ->implode('/');
    }

    /**
     * @return array{company_id: ?string, project_id: ?string, folder: ?ArchiveFolder, file: ?ArchiveFile}
     */
    private function resolveArchiveContext(CustomMedia $media): array
    {
        $folder = null;
        $file = null;

        $folderId = $this->resolveArchiveFolderId($media);
        if ($folderId) {
            $folder = ArchiveFolder::query()->withoutTenancy()->find($folderId);
        }

        $fileId = $this->resolveArchiveFileId($media);
        if ($fileId) {
            $file = ArchiveFile::query()->withoutTenancy()->with('folder')->find($fileId);
            if (!$folder && $file?->folder) {
                $folder = $file->folder;
            }
        }

        $model = $media->relationLoaded('model') || method_exists($media, 'model')
            ? $media->model
            : null;

        $companyId = $folder?->company_id
            ?? $file?->company_id
            ?? (is_object($model) && isset($model->company_id) ? $model->company_id : null)
            ?? (tenant('id') ? (string) tenant('id') : null);

        $projectId = $folder?->project_id
            ?? $file?->project_id
            ?? (is_object($model) && isset($model->project_id) ? $model->project_id : null);

        return [
            'company_id' => $companyId ? (string) $companyId : null,
            'project_id' => $projectId ? (string) $projectId : null,
            'folder' => $folder,
            'file' => $file,
        ];
    }

    private function resolveCompanyName(?string $companyId): string
    {
        $id = $companyId ?: (tenant('id') ? (string) tenant('id') : null);
        if (!$id) {
            return 'Unknown Company';
        }

        $company = Company::query()->find($id);
        if (!$company) {
            try {
                $company = Company::on('mysql')->find($id);
            } catch (Throwable) {
                $company = null;
            }
        }

        if ($company) {
            return $this->stringifyName($company->name) ?: 'Unknown Company';
        }

        if (tenant('id') && (string) tenant('id') === $id) {
            $tenantName = $this->stringifyName(tenant('name'));
            if ($tenantName !== '') {
                return $tenantName;
            }
        }

        return 'Unknown Company';
    }

    private function resolveProjectName(?string $projectId): ?string
    {
        if (!$projectId) {
            return null;
        }

        $project = ProjectManagement::query()->find($projectId);
        if (!$project) {
            return null;
        }

        $name = $this->stringifyName($project->name);

        return $name !== '' ? $name : null;
    }

    /**
     * @param  array{company_id: ?string, project_id: ?string, folder: ?ArchiveFolder, file: ?ArchiveFile}  $context
     */
    private function resolveArchivePath(CustomMedia $media, array $context, ?string $projectName): string
    {
        // Prefer real Archive Library folder tree (project → emergency → …)
        if ($context['folder'] instanceof ArchiveFolder) {
            return $this->folderHierarchyPath($context['folder']);
        }

        if ($context['file']?->folder instanceof ArchiveFolder) {
            return $this->folderHierarchyPath($context['file']->folder);
        }

        $model = $media->model;
        if ($model instanceof ArchiveFolder) {
            return $this->folderHierarchyPath($model);
        }
        if ($model instanceof ArchiveFile && $model->folder) {
            return $this->folderHierarchyPath($model->folder);
        }

        // Fallback when only storage path exists (should still include project name)
        $customPath = $media->getCustomProperty('file_path');
        $fallback = '';
        if (is_string($customPath) && $customPath !== '' && $customPath !== 'default' && $customPath !== 'default_path') {
            $fallback = trim($customPath, '/');
            $fallback = preg_replace('#^project-notifications/#', '', $fallback) ?? $fallback;
        }

        if ($projectName) {
            if ($fallback === '' || $fallback === 'files') {
                return $projectName;
            }
            if (!str_starts_with($fallback, $projectName . '/') && $fallback !== $projectName) {
                return $projectName . '/' . $fallback;
            }
        }

        return $fallback !== '' ? $fallback : ($projectName ?: 'files');
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

        // If the root folder name is missing/empty but we know the project, use project name.
        if (($parts[0] ?? '') === '' && $folder->project_id) {
            $projectName = $this->resolveProjectName((string) $folder->project_id);
            if ($projectName) {
                $parts[0] = $projectName;
            }
        }

        return implode('/', array_filter($parts, static fn ($part) => filled($part)));
    }

    private function stringifyName(mixed $name): string
    {
        if (is_array($name)) {
            $value = $name['ar'] ?? $name['en'] ?? reset($name);
            $name = is_string($value) ? $value : '';
        }

        $name = trim((string) $name);
        $name = str_replace(["\0", '/', '\\'], ['', '-', '-'], $name);

        return $name;
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
