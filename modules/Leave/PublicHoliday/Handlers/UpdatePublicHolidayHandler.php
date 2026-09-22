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
        $existing = $this->repository->getPublicHoliday($updatePublicHolidayCommand->getId());
        $dates = (new \Modules\Leave\PublicHoliday\Services\AnnualHolidayDateRange())->forYear(
            $updatePublicHolidayCommand->getDateStart()->format('m-d'),
            $updatePublicHolidayCommand->getDateEnd()->format('m-d'),
            (int) $existing->date_start->year,
        );
        if ($dates === null) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'date_start' => __('leave.public_holiday.invalid_year'),
            ]);
        }
        $data = $updatePublicHolidayCommand->toArray();
        $data['date_start'] = $dates[0]->format('Y-m-d');
        $data['date_end'] = $dates[1]->format('Y-m-d');
        $this->repository->updatePublicHoliday($updatePublicHolidayCommand->getId(), $data);

        $holiday = $this->repository->getPublicHoliday($updatePublicHolidayCommand->getId());
        $days = $this->dayCalculator->calculate($holiday->date_start, $holiday->date_end);
        $this->repository->syncPublicHolidayDays($holiday, $days);
    }
}
