<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

class RequestProjectNotificationTaskPostponementDTO
{
    public function __construct(
        public readonly string $newTaskDate,
        public readonly string $newTaskTime,
        public readonly string $reason,
        public readonly ?string $internalProcedureSettingId = null,
    ) {}
}
