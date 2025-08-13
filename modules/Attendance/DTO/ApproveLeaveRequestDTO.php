<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class ApproveLeaveRequestDTO
{
    public function __construct(
        public string $approverId,
        public ?string $notes = null
    ) {
    }

    /**
     * Get the approver ID.
     */
    public function getApproverId(): string
    {
        return $this->approverId;
    }

    /**
     * Get the notes.
     */
    public function getNotes(): ?string
    {
        return $this->notes;
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'approver_id' => $this->approverId,
            'notes' => $this->notes,
        ];
    }
}
