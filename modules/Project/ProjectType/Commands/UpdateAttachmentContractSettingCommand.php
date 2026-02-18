<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateAttachmentContractSettingDTO;

class UpdateAttachmentContractSettingCommand
{
    public function __construct(
        public readonly int $projectTypeId,
        public readonly UpdateAttachmentContractSettingDTO $dto
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
