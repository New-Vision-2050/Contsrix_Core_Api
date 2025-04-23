<?php

namespace Modules\PageBuilder\DTO;

class RelationshipDTO
{
    public string $type;
    public string $model;
    public string $foreignKey;
    public string $localKey;

    public function __construct(
        string $type,
        string $model,
        string $foreignKey,
        string $localKey
    ) {
        $this->type = $type;
        $this->model = $model;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            type: $data['type'],
            model: $data['model'],
            foreignKey: $data['foreign_key'],
            localKey: $data['local_key']
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'model' => $this->model,
            'foreign_key' => $this->foreignKey,
            'local_key' => $this->localKey,
        ];
    }
}