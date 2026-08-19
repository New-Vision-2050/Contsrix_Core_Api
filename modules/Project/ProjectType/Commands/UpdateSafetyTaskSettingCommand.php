<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateSafetyTaskSettingDTO;

class UpdateSafetyTaskSettingCommand
{
    public function __construct(public readonly int $projectTypeId, public readonly UpdateSafetyTaskSettingDTO $dto) {}
}