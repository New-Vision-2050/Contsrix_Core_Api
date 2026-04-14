<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Repositories;

use BasePackage\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
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


    public function paginatedWithConditions(array $conditions = [], $page = 1, $perPage = 10)
    {
        $query = $this->model->where($conditions)->with('days')->filter(request()->all());
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
        ])->load('days');
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

    public function getForExport(array $filters = []): SupportCollection
    {
        $query = $this->model->newQuery()
            ->with('country:id,name');

        // Apply name filter if provided
        if (!empty($filters['name'])) {
            $query->where('name', 'LIKE', '%' . $filters['name'] . '%');
        }

        // Apply country_id filter if provided
        if (isset($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
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
