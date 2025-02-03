<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Commands;

use Ramsey\Uuid\UuidInterface;

class ActivateCompanyCommand
{
    public function __construct(
        private UuidInterface $id,
        private int $is_active,
        private ?string $date_activate = null,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getIsActive(): int
    {
        return $this->is_active;
    }

    public function getDateActivate(): ?string
    {
        return $this->date_activate;
    }

    public function toArray(): array
    {
        return array_filter([
            'is_active' => $this->is_active,
            'date_activate' => $this->date_activate,
        ], fn($value) => $value !== null);
    }
}
