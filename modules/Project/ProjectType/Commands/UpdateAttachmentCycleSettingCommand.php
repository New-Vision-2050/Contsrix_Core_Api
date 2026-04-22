<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateAttachmentCycleSettingDTO;

class UpdateAttachmentCycleSettingCommand
{
    public function __construct(
        public readonly int $projectTypeId,
        public readonly UpdateAttachmentCycleSettingDTO $dto
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
