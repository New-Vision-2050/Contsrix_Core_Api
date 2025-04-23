<?php

namespace Modules\PageBuilder\DTO;

class TableSchemaDTO
{
    public string $name;
    /** @var ColumnDTO[] */
    public array $columns;
    /** @var RelationshipDTO[] */
    public array $relationships;

    public function __construct(
        string $name,
        array $columns,
        array $relationships = []
    ) {
        $this->name = $name;
        $this->columns = $columns;
        $this->relationships = $relationships;
    }

    public static function fromArray(array $data): self
    {
        $columns = array_map(
            fn (array $column) => ColumnDTO::fromArray($column),
            $data['columns']
        );

        $relationships = array_map(
            fn (array $relation) => RelationshipDTO::fromArray($relation),
            $data['relationships'] ?? []
        );

        return new self(
            name: $data['name'],
            columns: $columns,
            relationships: $relationships
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'columns' => array_map(
                fn (ColumnDTO $column) => $column->toArray(),
                $this->columns
            ),
            'relationships' => array_map(
                fn (RelationshipDTO $relation) => $relation->toArray(),
                $this->relationships
            ),
        ];
    }
}