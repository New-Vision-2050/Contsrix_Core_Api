<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Presenters;

use Illuminate\Support\Collection;
use Modules\ArchiveLibrary\File\Models\File;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\Audit\Presenters\AuditPresenter;

class FilePresenter extends AbstractPresenter
{
    private File $file;
    private static ?array $auditsCache = null;
    private static ?array $favouritesCache = null;

    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Override collection method to prime the audit cache
     */
    public static function collection(iterable $collection, ...$additionalParams): array
    {
        // Prime cache before processing collection
        self::primeAuditsCache(collect($collection));
        self::primeFavouritesCache(collect($collection));

        // Process collection normally
        $result = parent::collection($collection, ...$additionalParams);

        // Clear cache after processing
        self::clearAuditsCache();
        self::clearFavouritesCache();

        return $result;
    }

    /**
     * Prime the audits cache with a single query
     */
    private static function primeAuditsCache(Collection $files): void
    {
        if ($files->isEmpty()) {
            self::$auditsCache = [];
            return;
        }

        $fileIds = $files->pluck('id')->toArray();

        // Get all latest audits for all files in a single query
        $audits = \Modules\Audit\Models\Audit::whereIn('auditable_id', $fileIds)
            ->where('auditable_type', \Modules\ArchiveLibrary\File\Models\File::class)
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
     * Prime the favourites cache with a single query
     */
    private static function primeFavouritesCache(Collection $files): void
    {
        if ($files->isEmpty() || !auth()->check()) {
            self::$favouritesCache = [];
            return;
        }

        $fileIds = $files->pluck('id')->toArray();
        $userId = auth()->id();

        // Get all favourited file IDs for current user in a single query
        $favouritedFileIds = \DB::table('users_file_favourites')
            ->whereIn('file_id', $fileIds)
            ->where('user_id', $userId)
            ->pluck('file_id')
            ->toArray();

        // Create a map of file_id => is_favourite
        self::$favouritesCache = array_fill_keys($favouritedFileIds, true);
    }

    /**
     * Clear the favourites cache
     */
    public static function clearFavouritesCache(): void
    {
        self::$favouritesCache = null;
    }

    protected function present(bool $isListing = false): array
    {
        $file =$this->file->getFirstMedia('upload');
        if($file)
        {
            $file = (new MediaPresenter($this->file->getFirstMedia('upload')))->getData();
        }
        elseif ($this->file->mediaFile)
        {
            $file = (new MediaPresenter($this->file->mediaFile))->getData();
        }
        else{
            $file = null;
        }
        return [
            'id' => $this->file->id,
            'name' => $this->file->name,
            'reference_number' => $this->file->reference_number,
            'start_date' => $this->file->start_date?->format('Y-m-d'),
            'end_date' => $this->file->end_date?->format('Y-m-d'),
            'project_id' => $this->file->project_id,
            'access_type' => $this->file->access_type,
            'status' => $this->file->status ?? 1,
            'file' => $file,
            "is_file"=>1,
            "can_delete"=>$this->file->management_hierarchy_id ==null ? 1 :0 ,
            "can_update"=>$this->file->management_hierarchy_id ==null ? 1 :0 ,
            'is_favourite' => $this->isFavourite()==true ? 1:0,
            'users' => $this->file->users ? $this->file->users->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
            ]) : [],
            "created_at"=>$this->file->created_at,
            "updated_at"=>$this->file->updated_at,
            "last_log" => $this->getLastAudit(),
        ];
    }

    private function getLastAudit(): ?array
    {
        // Use cache if available
        if (self::$auditsCache !== null) {
            $lastAudit = self::$auditsCache[$this->file->id] ?? null;
            return $lastAudit ? (new AuditPresenter($lastAudit))->getData() : null;
        }

        // Fallback to direct query if cache not primed (single item presentation)
        $lastAudit = \Modules\Audit\Models\Audit::where('auditable_id', $this->file->id)
            ->where('auditable_type', \Modules\ArchiveLibrary\File\Models\File::class)
            ->orderBy('created_at', 'desc')
            ->first();

        return $lastAudit ? (new AuditPresenter($lastAudit))->getData() : null;
    }

    private function isFavourite(): bool
    {
        // Return false if user is not authenticated
        if (!auth()->check()) {
            return false;
        }

        // Use cache if available
        if (self::$favouritesCache !== null) {
            return isset(self::$favouritesCache[$this->file->id]);
        }

        // Fallback to direct query if cache not primed (single item presentation)
        return \DB::table('users_file_favourites')
            ->where('file_id', $this->file->id)
            ->where('user_id', auth()->id())
            ->exists();
    }
}
