<?php

namespace Modules\PageBuilder\DTO;

class TableSchemaDTO
{
    public function __construct(
        public readonly string $name,
        public readonly array $columns,
        public readonly array $relationships = []
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_map(fn($column) => $column instanceof ColumnDTO ? $column->toArray() : $column, $this->columns),
            'relationships' => array_map(fn($relation) => $relation instanceof RelationshipDTO ? $relation->toArray() : $relation, $this->relationships),
        ];
    }
}
