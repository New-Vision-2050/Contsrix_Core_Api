<?php

declare(strict_types=1);

namespace Modules\DocumentType\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateDocumentTypeCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name,
        private ?bool $is_active
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->name !== null) {
            $data['name'] = $this->name;
        }

        if ($this->is_active !== null) {
            $data['is_active'] = $this->is_active;
        }

        return $data;
    }
}
