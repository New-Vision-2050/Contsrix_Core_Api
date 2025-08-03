<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\DTO;

class ProgramPayloadDTO
{
    public function __construct(
        public readonly string $id,
        public readonly array $subEntities,
        public readonly array $children = []
    ) {
    }

    public static function fromArray(array $data): self
    {
        $children = isset($data['children'])
            ? array_map(fn ($child) => self::fromArray($child), $data['children'])
            : [];

        return new self(
            id: $data['id'],
            subEntities: $data['sub_entities'] ?? [],
            children: $children
        );
    }
}
