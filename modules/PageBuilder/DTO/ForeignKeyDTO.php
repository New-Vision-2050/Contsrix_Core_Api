<?php

namespace Modules\PageBuilder\DTO;

class ForeignKeyDTO
{
    public string $references;
    public string $on;

    public function __construct(
        string $references,
        string $on
    ) {
        $this->references = $references;
        $this->on = $on;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            references: $data['references'],
            on: $data['on']
        );
    }

    public function toArray(): array
    {
        return [
            'references' => $this->references,
            'on' => $this->on,
        ];
    }
}
