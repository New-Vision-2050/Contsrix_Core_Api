<?php

declare(strict_types=1);

namespace Modules\Stakeholder\DTO;

class CreateStakeholderDTO
{
    public function __construct(
        public string $name,
        public ?int $status = 1,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'status' => $this->status ?? 1,
        ];
    }
}
