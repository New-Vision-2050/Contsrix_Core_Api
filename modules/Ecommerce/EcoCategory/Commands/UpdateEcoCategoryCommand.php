<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoCategoryCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private ?string $description,
        private ?string $perentId
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'parent_id' => $this->perentId
        ]);

    }
}
