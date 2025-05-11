<?php

declare(strict_types=1);

namespace Modules\SubEntity\Commands;

use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;
use Ramsey\Uuid\UuidInterface;

class UpdateSubEntityStatusCommand
{
    public function __construct(
        private UuidInterface $id,
        private bool $isActive,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getStatus(): bool
    {
        return $this->isActive;
    }

    public function toArray(): array
    {
        return [
            'is_active' => $this->isActive,
        ];
    }
}
