<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class DismissViolationDTO
{
    public function __construct(
        public string $violation_id,
        public string $dismissed_by,
        public ?string $dismissal_reason = null,
    ) {}

    public function toArray(): array
    {
        return [
            'violation_id' => $this->violation_id,
            'dismissed_by' => $this->dismissed_by,
            'dismissal_reason' => $this->dismissal_reason,
        ];
    }

    public function getViolationId(): string
    {
        return $this->violation_id;
    }

    public function getDismissedBy(): string
    {
        return $this->dismissed_by;
    }

    public function getDismissalReason(): ?string
    {
        return $this->dismissal_reason;
    }
}
