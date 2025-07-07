<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class FilterAttendanceDTO
{
    public function __construct(
        public string $company_id,
        public ?string $user_id = null,
        public ?string $status = null,
        public ?string $start_date = null,
        public ?string $end_date = null,
        public ?string $department_id = null,
        public ?string $user_name = null,
        public ?string $user_email = null,
        public ?float $work_hours_from = null,
        public ?float $work_hours_to = null,
        public ?float $break_duration_from = null,
        public ?float $break_duration_to = null,
        public ?float $overtime_hours_from = null,
        public ?float $overtime_hours_to = null,
        public ?string $location = null,
        public ?string $ip_address = null,
        public ?bool $late_arrival = null,
        public ?bool $early_departure = null,
        // public ?int $page = null,
        // public ?int $per_page = null,
    ) {}

    public function toArray(): array
    {
        $data = ['company_id' => $this->company_id];

        if ($this->user_id !== null) {
            $data['user_id'] = $this->user_id;
        }
        if ($this->status !== null) {
            $data['status'] = $this->status;
        }
        if ($this->start_date !== null) {
            $data['start_date'] = $this->start_date;
        }
        if ($this->end_date !== null) {
            $data['end_date'] = $this->end_date;
        }
        if ($this->department_id !== null) {
            $data['department_id'] = $this->department_id;
        }
        if ($this->user_name !== null) {
            $data['user_name'] = $this->user_name;
        }
        if ($this->user_email !== null) {
            $data['user_email'] = $this->user_email;
        }
        if ($this->work_hours_from !== null) {
            $data['work_hours_from'] = $this->work_hours_from;
        }
        if ($this->work_hours_to !== null) {
            $data['work_hours_to'] = $this->work_hours_to;
        }
        if ($this->break_duration_from !== null) {
            $data['break_duration_from'] = $this->break_duration_from;
        }
        if ($this->break_duration_to !== null) {
            $data['break_duration_to'] = $this->break_duration_to;
        }
        if ($this->overtime_hours_from !== null) {
            $data['overtime_hours_from'] = $this->overtime_hours_from;
        }
        if ($this->overtime_hours_to !== null) {
            $data['overtime_hours_to'] = $this->overtime_hours_to;
        }
        if ($this->location !== null) {
            $data['location'] = $this->location;
        }
        if ($this->ip_address !== null) {
            $data['ip_address'] = $this->ip_address;
        }
        if ($this->late_arrival !== null) {
            $data['late_arrival'] = $this->late_arrival;
        }
        if ($this->early_departure !== null) {
            $data['early_departure'] = $this->early_departure;
        }

        return $data;
    }

    public function getCompanyId(): string
    {
        return $this->company_id;
    }

    public function getUserId(): ?string
    {
        return $this->user_id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getStartDate(): ?string
    {
        return $this->start_date;
    }

    public function getEndDate(): ?string
    {
        return $this->end_date;
    }

    public function getDepartmentId(): ?string
    {
        return $this->department_id;
    }

    public function getUserName(): ?string
    {
        return $this->user_name;
    }

    public function getUserEmail(): ?string
    {
        return $this->user_email;
    }

    public function getWorkHoursFrom(): ?float
    {
        return $this->work_hours_from;
    }

    public function getWorkHoursTo(): ?float
    {
        return $this->work_hours_to;
    }

    public function getBreakDurationFrom(): ?float
    {
        return $this->break_duration_from;
    }

    public function getBreakDurationTo(): ?float
    {
        return $this->break_duration_to;
    }

    public function getOvertimeHoursFrom(): ?float
    {
        return $this->overtime_hours_from;
    }

    public function getOvertimeHoursTo(): ?float
    {
        return $this->overtime_hours_to;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function isLateArrival(): ?bool
    {
        return $this->late_arrival;
    }

    public function isEarlyDeparture(): ?bool
    {
        return $this->early_departure;
    }

    // public function getPage(): ?int
    // {
    //     return $this->page;
    // }

    // public function getPerPage(): ?int
    // {
    //     return $this->per_page;
    // }
}
