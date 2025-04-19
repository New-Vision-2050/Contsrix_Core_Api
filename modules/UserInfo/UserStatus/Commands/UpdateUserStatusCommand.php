<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateUserStatusCommand
{
    public UuidInterface $companyUserId;

    public function __construct(
        private UuidInterface $id,
        private string $active_type,
        private ?string $active_date_to,
    ) {}

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function toArray(): array
    {
        return array_filter([
            'active_type' => $this->active_type,
            'active_date_to' => $this->active_date_to
        ]);
    }
}
