<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdatePasswordCommand
{
    public UuidInterface $companyUserId;

    public function __construct(
        private UuidInterface $id,
        private ?string $password,
        private string $type,
    ) {}

    public function getId(): UuidInterface
    {
        return $this->id;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return array_filter([
            'password' => $this->password
        ]);
    }
}
