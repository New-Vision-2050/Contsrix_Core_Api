<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSuperEntityAttributesConfigCommand
{
    public function __construct(
        private string $id,
        private array $defaultAttributes,
        private array $optionalAttributes,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return [
            'default_attributes' => $this->defaultAttributes,
            'optional_attributes' => $this->optionalAttributes,
        ];
    }
}
