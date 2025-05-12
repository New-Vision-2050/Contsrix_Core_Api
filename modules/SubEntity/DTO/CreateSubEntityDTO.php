<?php

declare(strict_types=1);

namespace Modules\SubEntity\DTO;

class CreateSubEntityDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $super_entity,
        public string $icon,
        public string $main_program_id,
        public bool $is_active,
        public bool $is_registrable,
        public array $default_attributes,
        public ?array $optional_attributes,
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'super_entity' => $this->super_entity,
            'icon' => $this->icon,
            'main_program_id' => $this->main_program_id,
            'is_active' => $this->is_active,
            'is_registrable' => $this->is_registrable,
            'default_attributes' => $this->default_attributes,
            'optional_attributes' => $this->optional_attributes,
        ];
    }
}
