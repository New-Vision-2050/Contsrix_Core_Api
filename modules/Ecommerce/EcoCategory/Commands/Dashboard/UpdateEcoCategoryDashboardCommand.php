<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoCategory\Commands\Dashboard;

use Ramsey\Uuid\UuidInterface;

class UpdateEcoCategoryDashboardCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?array $name, // Changed to array for multilingual support
        private ?string $perentId,
        private ?int $priority = null,
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
            'parent_id' => $this->perentId,
            'priority' => $this->priority,
        ], function ($value) {
            return $value !== null;
        });
    }
}
