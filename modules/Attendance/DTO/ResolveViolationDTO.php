<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class ResolveViolationDTO
{
    public function __construct(
        public string $violation_id,
        public string $resolved_by,
        public ?string $resolution_notes = null,
    ) {}

    public function toArray(): array
    {
        return [
            'violation_id' => $this->violation_id,
            'resolved_by' => $this->resolved_by,
            'resolution_notes' => $this->resolution_notes,
        ];
    }

    public function getViolationId(): string
    {
        return $this->violation_id;
    }

    public function getResolvedBy(): string
    {
        return $this->resolved_by;
    }

    public function getResolutionNotes(): ?string
    {
        return $this->resolution_notes;
    }
}
