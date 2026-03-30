<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\DTO;

class CreateProcedureSettingDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly string $execute_type,
        public readonly ?string $icon = null,
        public readonly float $percentage = 0,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name'         => $this->name,
            'type'         => $this->type,
            'execute_type' => $this->execute_type,
            'icon'         => $this->icon,
            'percentage'   => $this->percentage,
        ];
    }
}
