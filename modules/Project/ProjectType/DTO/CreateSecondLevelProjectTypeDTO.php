<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class CreateSecondLevelProjectTypeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $icon = null,
        public readonly ?int $parent_id = null,
        public readonly ?int $reference_project_type_id = null,
        public readonly array $schema_ids = [],
        public readonly bool $is_active = true,
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'reference_project_type_id' => $this->reference_project_type_id,
            'company_id' => tenant('id'),
            'is_have_schema' => true,
            'is_active' => $this->is_active,
            'is_created' => true,
        ];
    }

    public function getSchemaIds(): array
    {
        return $this->schema_ids;
    }
}
