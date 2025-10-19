<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Commands\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoBrandDashboardCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name, // Changed to array for multilingual support
        private ?array $description // Changed to array for multilingual support
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
        ], function ($value) {
            return $value !== null;
        });
    }
}
