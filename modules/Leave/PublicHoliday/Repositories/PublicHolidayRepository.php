<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use Ramsey\Uuid\UuidInterface;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Illuminate\Support\Collection as SupportCollection;


/**
 * @property PublicHoliday $model
 * @method PublicHoliday findOneOrFail($id)
 * @method PublicHoliday findOneByOrFail(array $data)
 */
class PublicHolidayRepository extends BaseRepository
{
    public function __construct(PublicHoliday $model)
    {
        parent::__construct($model);
    }

    public function getPublicHolidayList(?int $page, ?int $perPage = 10): Collection
    {
        return $this->paginatedList([], $page, $perPage);
    }


    public function paginatedWithConditions(array $conditions = [], $page = 1, $perPage = 10, ?int $periodYear = null, ?int $periodMonth = null, ?array $filters = null)
    {
        $query = $this->model->where($conditions)->with(['days', 'branch'])->filter($filters ?? request()->all());
        if ($periodYear !== null) {
            $start = Carbon::create($periodYear, $periodMonth ?? 1, 1)->startOfDay();
            $end = $periodMonth !== null ? $start->copy()->endOfMonth() : $start->copy()->endOfYear();
            $query->inDateRange($start->toDateString(), $end->toDateString());
        }
        $count = $query->count();
        $paginatedData = $query->forPage($page, $perPage)->get();
        $paginationArray = $this->getPaginationInformation($page, $perPage, $count);
        return array_merge($paginationArray, [
            'data' => $paginatedData
        ]);
    }
    public function getPublicHoliday(UuidInterface $id): PublicHoliday
    {
        return $this->findOneByOrFail([
            'id' => $id->toString(),
        ])->load(['days', 'branch']);
    }

    public function createPublicHoliday(array $data): PublicHoliday
    {
        return $this->create($data+["year"=>Carbon::parse($data['date_start'])->year,"holiday_type"=>"national"]);
    }

    /**
     * @param array<int, array{date: \Carbon\CarbonInterface, is_compensation: bool}> $days
     */
    public function syncPublicHolidayDays(PublicHoliday $publicHoliday, array $days): void
    {
        $publicHoliday->days()->delete();

        foreach ($days as $day) {
            $publicHoliday->days()->create([
                'date' => $day['date'],
                'is_compensation' => $day['is_compensation'],
            ]);
        }
    }

    public function updatePublicHoliday(UuidInterface $id, array $data): bool
    {
        return $this->update($id, $data+["year"=>Carbon::parse($data['date_start'])->year,"holiday_type"=>"national"]);
    }

    public function deletePublicHoliday(UuidInterface $id): bool
    {
        return $this->delete($id);
    }

    public function getBranchesForCards(): Collection
    {
        return ManagementHierarchy::query()->without('user')
            ->where('company_id', tenant('id'))
            ->where('type', 'branch')
            ->orderBy('name')->orderBy('id')
            ->get(['id', 'name']);
    }

    public function getHolidayPeriodsForBranches(array $branchIds): Collection
    {
        return $this->model->newQuery()->whereIn('branch_id', $branchIds)
            ->where('holiday_type', 'national')
            ->get(['branch_id', 'date_start', 'date_end']);
    }

    public function getForExport(array $filters = []): SupportCollection
    {
        $query = $this->model->newQuery()
            ->with('branch:id,name');

        // Apply name filter if provided
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Apply branch_id filter if provided
        if (isset($filters['branch_id'])) {
            $query->where('branch_id', $filters['branch_id']);
        }

        // Apply date_start filter if provided
        if (!empty($filters['date_start'])) {
            $query->where('date_start', '>=', $filters['date_start']);
        }

        // Apply date_end filter if provided
        if (!empty($filters['date_end'])) {
            $query->where('date_end', '<=', $filters['date_end']);
        }

        // Apply specific IDs filter if provided
        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $query->whereIn('id', $filters['ids']);
        }

        return $query->get();
    }
}
