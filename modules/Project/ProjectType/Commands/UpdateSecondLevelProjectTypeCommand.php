<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

class UpdateSecondLevelProjectTypeCommand
{
    public function __construct(
        private readonly int $id,
        private readonly string $name,
        private readonly ?string $icon = null,
        private readonly ?int $parent_id = null,
        private readonly ?int $reference_project_type_id = null,
        private readonly array $schema_ids = [],
        private readonly bool $is_active = true,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getParentId(): ?int
    {
        return $this->parent_id;
    }

    public function getReferenceProjectTypeId(): ?int
    {
        return $this->reference_project_type_id;
    }

    public function getSchemaIds(): array
    {
        return $this->schema_ids;
    }

    public function getIsActive(): bool
    {
        return $this->is_active;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'reference_project_type_id' => $this->reference_project_type_id,
            'is_have_schema' => true,
            'is_active' => $this->is_active,
            'is_created' => true,
        ];
    }
}
