<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\Leave\PublicHoliday\DTO\CreatePublicHolidayDTO;
use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;
use Ramsey\Uuid\UuidInterface;

class PublicHolidayCRUDService
{
    public function __construct(
        private PublicHolidayRepository $repository,
        private PublicHolidayDayCalculator $dayCalculator,
    ) {
    }

    public function create(CreatePublicHolidayDTO $createPublicHolidayDTO): PublicHoliday
    {
        $holiday = $this->repository->createPublicHoliday($createPublicHolidayDTO->toArray());

        $days = $this->dayCalculator->calculate(
            Carbon::parse($holiday->date_start),
            Carbon::parse($holiday->date_end),
        );
        $this->repository->syncPublicHolidayDays($holiday, $days);

        $holiday->load(['days', 'branch']);

        return $holiday;
    }

    public function list(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        return $this->repository->paginatedWithConditions(
            ["holiday_type" => "national"],
            page: $page,
            perPage: $perPage,
            periodYear: (int) ($filters['year'] ?? date('Y')),
            periodMonth: isset($filters['month']) ? (int) $filters['month'] : null,
            filters: $filters,
        );
    }

    public function branchCards(): array
    {
        $branches = $this->repository->getBranchesForCards();
        $holidays = $this->repository->getHolidayPeriodsForBranches($branches->pluck('id')->all())
            ->groupBy('branch_id');

        return $branches->map(function ($branch) use ($holidays) {
            $years = [];
            foreach ($holidays->get($branch->id, collect()) as $holiday) {
                for ($year = $holiday->date_start->year; $year <= $holiday->date_end->year; ++$year) {
                    $years[$year] = $year;
                }
            }
            sort($years, SORT_NUMERIC);

            return [
                'branch_id' => $branch->id,
                'name' => $branch->name,
                'years' => array_values($years),
            ];
        })->all();
    }

    public function get(UuidInterface $id): PublicHoliday
    {
        return $this->repository->getPublicHoliday(
            id: $id,
        );
    }
    public function getForExport(array $filters = []): Collection
    {
        return $this->repository->getForExport($filters);
    }
}
