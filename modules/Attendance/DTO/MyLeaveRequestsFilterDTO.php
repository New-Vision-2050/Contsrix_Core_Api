<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class MyLeaveRequestsFilterDTO
{
    public function __construct(
        public string $userId,
        public ?string $status = null,
        public ?string $leaveTypeId = null,
        public ?string $startDate = null,
        public ?string $endDate = null
    ) {
    }

    /**
     * Get the user ID.
     */
    public function getUserId(): string
    {
        return $this->userId;
    }

    /**
     * Get the status filter.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Get the leave type ID filter.
     */
    public function getLeaveTypeId(): ?string
    {
        return $this->leaveTypeId;
    }

    /**
     * Get the start date filter.
     */
    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    /**
     * Get the end date filter.
     */
    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'user_id' => $this->userId,
            'status' => $this->status,
            'leave_type_id' => $this->leaveTypeId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ], fn($value) => $value !== null);
    }
}
