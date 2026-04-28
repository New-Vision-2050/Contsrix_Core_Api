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
        public readonly ?int $deadline_days = null,
        public readonly ?int $deadline_hours = null,
        public readonly ?string $escalation_user_id = null,
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
            'deadline_days'  => $this->deadline_days,
            'deadline_hours' => $this->deadline_hours,
            'escalation_user_id' => $this->escalation_user_id,
        ];
    }
}
