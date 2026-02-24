<?php

declare(strict_types=1);

namespace Modules\Project\TermSetting\Commands;

class UpdateTermSettingCommand
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $description = null,
        private ?int $parentId = null,
        private ?int $projectTypeId = null,
        private array $termServicesIds = [],
        private ?bool $isActive = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function getProjectTypeId(): ?int
    {
        return $this->projectTypeId;
    }

    public function getTermServicesIds(): array
    {
        return $this->termServicesIds;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->parentId,
            'project_type_id' => $this->projectTypeId,
        ];

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        return $data;
    }
}
