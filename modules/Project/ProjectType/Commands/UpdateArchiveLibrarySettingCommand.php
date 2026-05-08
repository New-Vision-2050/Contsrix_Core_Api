<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

use Modules\Project\ProjectType\DTO\UpdateArchiveLibrarySettingDTO;

class UpdateArchiveLibrarySettingCommand
{
    public function __construct(
        public readonly int $projectTypeId,
        public readonly UpdateArchiveLibrarySettingDTO $dto
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
