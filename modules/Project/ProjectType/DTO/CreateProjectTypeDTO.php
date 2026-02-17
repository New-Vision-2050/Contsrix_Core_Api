<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\DTO;

class CreateProjectTypeDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $icon = null,
        public readonly ?int $parent_id = null,
        public readonly bool $is_have_schema = false,
        public readonly bool $is_active = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'icon' => $this->icon,
            'parent_id' => $this->parent_id,
            'is_have_schema' => $this->is_have_schema,
            'is_active' => $this->is_active,
            'is_created' => true,
        ];
    }
}
