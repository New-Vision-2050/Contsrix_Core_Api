<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSubEntityAttributesCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $default_attributes,
        private ?string $optional_attributes,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getDefaultAttributes(): string
    {
        return $this->default_attributes;
    }

    public function getOptionalAttributes(): ?string
    {
        return $this->optional_attributes;
    }

    public function toArray(): array
    {
        return [
            'default_attributes' => $this->default_attributes,
            'optional_attributes' => $this->optional_attributes,
        ];
    }
}
