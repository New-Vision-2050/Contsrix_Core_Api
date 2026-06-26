<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateMaintenanceEmergencySettingDTO;

class UpdateMaintenanceEmergencySettingCommand
{
    public function __construct(
        public readonly int $projectTypeId,
        public readonly UpdateMaintenanceEmergencySettingDTO $dto
    ) {
    }

    public function toArray(): array
    {
        return [
            'project_type_id' => $this->projectTypeId,
            'data' => $this->dto->toArray(),
        ];
    }
}
