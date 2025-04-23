<?php

namespace Modules\PageBuilder\DTO;

class ColumnDTO
{
    public string $name;
    public string $type;
    public bool $required;
    public bool $nullable;
    public mixed $default;
    public ?ForeignKeyDTO $foreignKey;

    public function __construct(
        string $name,
        string $type,
        bool $required,
        bool $nullable,
        mixed $default = null,
        ?ForeignKeyDTO $foreignKey = null
    ) {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
        $this->nullable = $nullable;
        $this->default = $default;
        $this->foreignKey = $foreignKey;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            required: $data['required'] ?? !($data['nullable'] ?? false),
            nullable: $data['nullable'] ?? false,
            default: $data['default'] ?? null,
            foreignKey: isset($data['foreign_key']) ? ForeignKeyDTO::fromArray($data['foreign_key']) : null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'required' => $this->required,
            'nullable' => $this->nullable,
            'default' => $this->default,
            'foreign_key' => $this->foreignKey?->toArray(),
        ];
    }
}