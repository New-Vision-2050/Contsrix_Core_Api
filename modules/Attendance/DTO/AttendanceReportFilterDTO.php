<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

use Carbon\Carbon;

final class AttendanceReportFilterDTO
{
    public function __construct(
        public string $company_id,
        public string $employee_id,
        public ?string $from_date = null,
        public ?string $to_date = null,
        public ?int $year = null,
        public ?int $month = null,
        public int $page = 1,
        public int $per_page = 12,
    ) {}

    public function toArray(): array
    {
        $data = [
            'company_id' => $this->company_id,
            'employee_id' => $this->employee_id,
        ];

        foreach ([
            'from_date',
            'to_date',
            'year',
            'month',
            'page',
            'per_page',
        ] as $key) {
            if ($this->{$key} !== null) {
                $data[$key] = $this->{$key};
            }
        }

        return $data;
    }

    public function periodStart(): string
    {
        if ($this->from_date !== null) {
            return $this->from_date;
        }

        if ($this->year !== null && $this->month !== null) {
            return sprintf('%04d-%02d-01', $this->year, $this->month);
        }

        if ($this->year !== null) {
            return sprintf('%04d-01-01', $this->year);
        }

        return now()->startOfYear()->toDateString();
    }

    public function periodEnd(): string
    {
        if ($this->to_date !== null) {
            return $this->to_date;
        }

        if ($this->year !== null && $this->month !== null) {
            return Carbon::create($this->year, $this->month, 1)->endOfMonth()->toDateString();
        }

        if ($this->year !== null) {
            return sprintf('%04d-12-31', $this->year);
        }

        return now()->endOfMonth()->toDateString();
    }
}
