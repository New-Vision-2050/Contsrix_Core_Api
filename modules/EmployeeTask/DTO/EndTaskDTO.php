<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

final class EndTaskDTO
{
    public function __construct(
        public readonly float   $latitude,
        public readonly float   $longitude,
        public readonly ?string $notes = null,
        public readonly ?string $internalProcedureSettingId = null,
    ) {}
}
