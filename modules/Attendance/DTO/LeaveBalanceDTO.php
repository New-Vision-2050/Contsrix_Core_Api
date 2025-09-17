<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class LeaveBalanceDTO
{
    public function __construct(
        public string $userId,
        public ?string $leaveTypeId = null,
        public int $year = 0
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
     * Get the leave type ID.
     */
    public function getLeaveTypeId(): ?string
    {
        return $this->leaveTypeId;
    }

    /**
     * Get the year.
     */
    public function getYear(): int
    {
        return $this->year;
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
            'leave_type_id' => $this->leaveTypeId,
            'year' => $this->year ?: null,
        ], fn($value) => $value !== null);
    }
}
