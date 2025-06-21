<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class RejectLeaveRequestDTO
{
    public function __construct(
        public string $rejecterId,
        public string $reason = 'No reason provided'
    ) {
    }

    /**
     * Get the rejecter ID.
     */
    public function getRejecterId(): string
    {
        return $this->rejecterId;
    }

    /**
     * Get the rejection reason.
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rejecter_id' => $this->rejecterId,
            'reason' => $this->reason,
        ];
    }
}
