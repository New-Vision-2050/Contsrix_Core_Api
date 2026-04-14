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

        return $holiday->load('days');
    }

    public function list(int $page = 1, int $perPage = 10): array
    {
        return $this->repository->paginatedWithConditions(
            ["holiday_type"=>"national","year"=>date("Y")],
            page: $page,
            perPage: $perPage,
        );
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
