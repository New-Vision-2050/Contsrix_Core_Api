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

        // Process collection normally
        $result = parent::collection($collection, ...$additionalParams);

        // Clear cache after processing
        self::clearAuditsCache();

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

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->file->id,
            'name' => $this->file->name,
            'reference_number' => $this->file->reference_number,
            'start_date' => $this->file->start_date?->format('Y-m-d'),
            'end_date' => $this->file->end_date?->format('Y-m-d'),
            'access_type' => $this->file->access_type,
            'file' => $this->file->getFirstMedia('upload') ? (new MediaPresenter($this->file->getFirstMedia('upload')))->getData():(new MediaPresenter($this->file->mediaFile))->getData(),
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
}
