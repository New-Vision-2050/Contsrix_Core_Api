<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class LeaveConflictCheckDTO
{
    public function __construct(
        public string $userId,
        public string $startDate,
        public string $endDate,
        public ?string $excludeRequestId = null
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
     * Get the request ID to exclude.
     */
    public function getExcludeRequestId(): ?string
    {
        return $this->excludeRequestId;
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
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'exclude_request_id' => $this->excludeRequestId,
        ], fn($value) => $value !== null);
    }
}
