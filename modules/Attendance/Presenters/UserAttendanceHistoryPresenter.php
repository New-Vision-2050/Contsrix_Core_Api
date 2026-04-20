<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;

class UserAttendanceHistoryPresenter extends AbstractPresenter
{
    private array $dayData;

    public function __construct(array $dayData)
    {
        $this->dayData = $dayData;
    }

    public function present(bool $isListing = false): array
    {
        return [
            'date' => $this->dayData['date'] ?? null,
            'day_name' => $this->dayData['day_name'] ?? null,
            'status' => $this->dayData['status'] ?? 'غائب',
            'is_late' => (int) ($this->dayData['is_late'] ?? 0),
            'is_absent' => (int) ($this->dayData['is_absent'] ?? 0),
            'is_holiday' => (int) ($this->dayData['is_holiday'] ?? 0),
            'periods_count' => $this->dayData['periods_count'] ?? 0,
            'periods' => $this->dayData['periods'] ?? [],
        ];
    }
}

