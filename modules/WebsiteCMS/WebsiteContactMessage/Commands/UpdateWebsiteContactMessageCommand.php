<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateWebsiteContactMessageCommand
{
    public function __construct(
        private UuidInterface $id,
        private ?string $name,
        private ?string $phone,
        private ?string $email,
        private ?string $address,
        private ?int $status,
        private ?string $message,
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

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'status' => $this->status,
            'message' => $this->message,
        ], fn($value) => $value !== null);
    }
}
