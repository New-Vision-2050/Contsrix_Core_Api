<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSuperEntityAttributesConfigCommand
{
    public function __construct(
        private string $id,
        private array $allowedAttributes,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
    public function getAllowedAttributes(): array
    {
        return $this->allowedAttributes;
    }

    public function toArray(): array
    {
        return [
            'allowed_attributes' => $this->allowedAttributes,
        ];
    }
}
