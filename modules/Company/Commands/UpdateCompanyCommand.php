<?php

declare(strict_types=1);

namespace Modules\Company\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateCompanyCommand
{
    public function __construct(
        private UuidInterface $id,
        private string $name,
        private string $email,
        private string $phone,
    ) {
    }

    public function getId(): UuidInterface
    {
        return $this->id;
    }
    public function getName(): ?string
    {
        return $this->name;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);
    }
}
