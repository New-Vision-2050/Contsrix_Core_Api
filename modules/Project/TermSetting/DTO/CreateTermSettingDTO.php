<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\DTO;

class CreateTermSettingDTO
{
    public function __construct(
        public string $name,
        public ?string $description = null,
        public ?int $parentId = null,
        public ?int $projectTypeId = null,
        public array $termServicesIds = [],
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'project_type_id' => $this->projectTypeId,
            'is_active' => $this->isActive,
        ];
    }

    public function getTermServicesIds(): array
    {
        return $this->termServicesIds;
    }
}
