<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Presenters;

use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\Folder\Models\Folder;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\Audit\Presenters\AuditPresenter;

class FolderPresenter extends AbstractPresenter
{
    private Folder $folder;
    private static ?array $auditsCache = null;
    private static ?array $fileSizesCache = null;

    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * Override collection method to prime the audit and file sizes caches
     */
    public static function collection(iterable $collection, ...$additionalParams): array
    {
        // Prime caches before processing collection
        self::primeAuditsCache(collect($collection));
        self::primeFileSizesCache(collect($collection));

        // Process collection normally
        $result = parent::collection($collection, ...$additionalParams);

        // Clear caches after processing
        self::clearAuditsCache();
        self::clearFileSizesCache();

        return $result;
    }

    /**
     * Prime the audits cache with a single query
     */
    private static function primeAuditsCache(Collection $folders): void
    {
        if ($folders->isEmpty()) {
            self::$auditsCache = [];
            return;
        }

        $folderIds = $folders->pluck('id')->toArray();

        // Get all latest audits for all folders in a single query
        $audits = \Modules\Audit\Models\Audit::whereIn('auditable_id', $folderIds)
            ->where('auditable_type', \Modules\ArchiveLibrary\Folder\Models\Folder::class)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('auditable_id')
            ->map(fn($group) => $group->first())
            ->all();

        self::$auditsCache = $audits;
    }

    /**
     * Clear the audits cache
     */
    public static function clearAuditsCache(): void
    {
        self::$auditsCache = null;
    }

    /**
     * Prime the file sizes cache with a single query
     * Calculates total size of all media files for each folder
     */
    private static function primeFileSizesCache(Collection $folders): void
    {
        if ($folders->isEmpty()) {
            self::$fileSizesCache = [];
            return;
        }

        $folderIds = $folders->pluck('id')->toArray();

        // Get all files for all folders with their media sizes in a single optimized query
        $fileSizes = \Modules\ArchiveLibrary\File\Models\File::whereIn('folder_id', $folderIds)
            ->with(['media' => function ($query) {
                $query->select('id', 'model_id', 'size');
            }])
            ->get()
            ->groupBy('folder_id')
            ->map(function ($files) {
                // Sum all media sizes for all files in this folder
                return $files->sum(function ($file) {
                    return $file->media->sum('size');
                });
            })
            ->all();

        self::$fileSizesCache = $fileSizes;
    }

    /**
     * Clear the file sizes cache
     */
    public static function clearFileSizesCache(): void
    {
        self::$fileSizesCache = null;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->folder->id,
            'name' => $this->folder->name,
            'parent_id' => $this->folder?->parent_id,
            'access_type' => $this->folder->access_type,
            'file' => $this->folder->getFirstMedia("upload") ? (new MediaPresenter($this->folder->getFirstMedia('upload')))->getData(): null,
            'files_count' => $this->folder->files_count ?? $this->folder->files()->count(),
            'size' => $this->getFolderSize(),
            "created_at"=>$this->folder->created_at,
            "updated_at"=>$this->folder->updated_at,
            "is_password"=>$this->folder->password != null?1 : 0,
            "last_log" => $this->getLastAudit(),
        ];
    }

    private function getLastAudit(): ?array
    {
        // Use cache if available
        if (self::$auditsCache !== null) {
            $lastAudit = self::$auditsCache[$this->folder->id] ?? null;
            return $lastAudit ? (new AuditPresenter($lastAudit))->getData() : null;
        }

        // Fallback to direct query if cache not primed (single item presentation)
        $lastAudit = \Modules\Audit\Models\Audit::where('auditable_id', $this->folder->id)
            ->where('auditable_type', \Modules\ArchiveLibrary\Folder\Models\Folder::class)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastAudit ? (new AuditPresenter($lastAudit))->getData() : null;
    }

    private function getFolderSize(): int
    {
        // Use cache if available
        if (self::$fileSizesCache !== null) {
            return self::$fileSizesCache[$this->folder->id] ?? 0;
        }

        // Fallback to direct query if cache not primed (single item presentation)
        $totalSize = \Modules\ArchiveLibrary\File\Models\File::where('folder_id', $this->folder->id)
            ->with(['media' => function ($query) {
                $query->select('id', 'model_id', 'size');
            }])
            ->get()
            ->sum(function ($file) {
                return $file->media->sum('size');
            });

        return (int) $totalSize;
    }
}
