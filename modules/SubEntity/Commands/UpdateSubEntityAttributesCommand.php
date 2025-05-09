<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateSubEntityAttributesCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $default_attributes,
        private ?array $optional_attributes,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getDefaultAttributes(): array
    {
        return $this->default_attributes;
    }

    public function getOptionalAttributes(): ?array
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
