<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Handlers;

use Modules\Leave\PublicHoliday\Commands\UpdatePublicHolidayCommand;
use Modules\Leave\PublicHoliday\Repositories\PublicHolidayRepository;
use Modules\Leave\PublicHoliday\Services\PublicHolidayDayCalculator;

class UpdatePublicHolidayHandler
{
    public function __construct(
        private PublicHolidayRepository $repository,
        private PublicHolidayDayCalculator $dayCalculator,
    ) {
    }

    public function handle(UpdatePublicHolidayCommand $updatePublicHolidayCommand): void
    {
        $this->repository->updatePublicHoliday($updatePublicHolidayCommand->getId(), $updatePublicHolidayCommand->toArray());

        $holiday = $this->repository->getPublicHoliday($updatePublicHolidayCommand->getId());
        $days = $this->dayCalculator->calculate($holiday->date_start, $holiday->date_end);
        $this->repository->syncPublicHolidayDays($holiday, $days);
    }
}
