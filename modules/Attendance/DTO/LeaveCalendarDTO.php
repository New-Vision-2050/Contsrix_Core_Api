<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class LeaveCalendarDTO
{
    public function __construct(
        public string $startDate,
        public string $endDate,
        public ?string $companyId = null
    ) {
    }

    /**
     * Get the start date.
     */
    public function getStartDate(): string
    {
        return $this->startDate;
    }

    /**
     * Get the end date.
     */
    public function getEndDate(): string
    {
        return $this->endDate;
    }

    /**
     * Get the company ID.
     */
    public function getCompanyId(): ?string
    {
        return $this->companyId;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'company_id' => $this->companyId,
        ], fn($value) => $value !== null);
    }
}
