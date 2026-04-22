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
            'country_id' => $this->publicHoliday->country_id,
            'date_start' => $this->publicHoliday->date_start?->format('Y-m-d'),
            'date_end' => $this->publicHoliday->date_end?->format('Y-m-d'),
            'days' => $this->publicHoliday->relationLoaded('days')
                ? $this->publicHoliday->days->map(static function ($day) {
                    return [
                        'id' => $day->id,
                        'date' => $day->date?->format('Y-m-d'),
                        'is_compensation' => $day->is_compensation,
                    ];
                })->values()->all()
                : [],
            'country' => $this->publicHoliday->country ? [
                'id' => $this->publicHoliday->country->id,
                'name' => $this->publicHoliday->country->name,
            ] : null,
        ];
    }
}
