<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoProduct\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoProductCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name = null, // Make name an optional array for updates
        private ?array $description = null, // Add description as an optional array
        // Add other updateable properties here
        private ?float $price = null,
        private ?string $sku = null,
        // ... and so on for all updatable fields
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getName(): ?array
    {
        return $this->name;
    }

    public function getDescription(): ?array
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'sku' => $this->sku,
        ]);
    }
}
