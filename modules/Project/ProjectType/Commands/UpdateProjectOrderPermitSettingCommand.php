<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateProjectOrderPermitSettingDTO;

class UpdateProjectOrderPermitSettingCommand
{
    public function __construct(public readonly int $projectTypeId, public readonly UpdateProjectOrderPermitSettingDTO $dto) {}
}