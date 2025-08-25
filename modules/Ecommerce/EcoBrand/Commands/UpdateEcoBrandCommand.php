<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoBrandCommand
{
    public function __construct(
        private UuidInterface $id,
        private array $name,
        private ?array $description
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
            'description' => $this->description
        ]);
    }
}
