<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Commands;

class UpdateProjectTypeCommand
{
    public function __construct(
        private int $id,
        private string $name,
        private ?string $icon = null,
        private ?int $parent_id = null,
        private ?bool $is_have_schema = null,
        private ?bool $is_active = null,
    ) {
    }

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

    public function getIsHaveSchema(): ?bool
    {
        return $this->is_have_schema;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
        ];

        if ($this->icon !== null) {
            $data['icon'] = $this->icon;
        }

        if ($this->parent_id !== null) {
            $data['parent_id'] = $this->parent_id;
        }

        if ($this->is_have_schema !== null) {
            $data['is_have_schema'] = $this->is_have_schema;
        }

        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }

        return $data;
    }
}
