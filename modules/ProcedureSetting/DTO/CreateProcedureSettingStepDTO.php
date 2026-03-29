<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\DTO;

class CreateProcedureSettingStepDTO
{
    public function __construct(
        public readonly string $procedure_setting_id,
        public readonly ?string $employee_id = null,
        public readonly bool $is_accept = false,
        public readonly bool $is_approve = false,
        public readonly int $duration = 0,
        public readonly ?string $forms = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'procedure_setting_id' => $this->procedure_setting_id,
            'employee_id'          => $this->employee_id,
            'is_accept'            => $this->is_accept,
            'is_approve'           => $this->is_approve,
            'duration'             => $this->duration,
            'forms'                => $this->forms,
        ];
    }
}
