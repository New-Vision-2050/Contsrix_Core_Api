<?php

declare(strict_types=1);

namespace Modules\Leave\PublicHoliday\Presenters;

use Modules\Leave\PublicHoliday\Models\PublicHoliday;
use BasePackage\Shared\Presenters\AbstractPresenter;

class PublicHolidayPresenter extends AbstractPresenter
{
    private PublicHoliday $publicHoliday;

    public function __construct(PublicHoliday $publicHoliday)
    {
        $this->publicHoliday = $publicHoliday;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->publicHoliday->id,
            'name' => $this->publicHoliday->name_ar ?? $this->publicHoliday->name,
            'branch_id' => $this->publicHoliday->branch_id,
            'date_start' => $this->publicHoliday->date_start?->format('m-d'),
            'date_end' => $this->publicHoliday->date_end?->format('m-d'),
            'year' => $this->publicHoliday->year,
            'is_recurring' => $this->publicHoliday->is_recurring,
            'count_days' => $this->publicHoliday->relationLoaded('days')
                ? $this->publicHoliday->days->count()
                : $this->publicHoliday->days()->count(),
            'days' => $this->publicHoliday->relationLoaded('days')
                ? $this->publicHoliday->days->map(static function ($day) {
                    return [
                        'id' => $day->id,
                        'date' => $day->date?->format('Y-m-d'),
                        'is_compensation' => $day->is_compensation,
                    ];
                })->values()->all()
                : [],
            'branch' => $this->publicHoliday->branch ? [
                'id' => $this->publicHoliday->branch->id,
                'name' => $this->publicHoliday->branch->name,
            ] : null,
        ];
    }
}
