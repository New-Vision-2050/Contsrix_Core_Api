<?php declare(strict_types=1);

namespace Modules\Subscription\Package\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdatePackageStatusCommand
{
    public function __construct(
        private UuidInterface $id,
        private bool $status,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getStatus(): ?bool
    {
        return $this->status;
    }

    public function toArray(): array
    {
        return [
            'is_active' => $this->status,
        ];
    }
}
