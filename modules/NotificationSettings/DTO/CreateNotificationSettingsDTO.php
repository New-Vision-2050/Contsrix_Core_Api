<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateNotificationSettingsDTO
{
    public function __construct(
        public readonly string $type,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly string $reminderType = 'daily',
        public readonly ?string $message = null,
        public readonly bool $isActive = true,
        public readonly ?UuidInterface $userId = null,
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getReminderType(): string
    {
        return $this->reminderType;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function getUserId(): ?UuidInterface
    {
        return $this->userId;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'reminder_type' => $this->reminderType,
            'message' => $this->message,
            'is_active' => $this->isActive,
            'user_id' => $this->userId?->toString(),
        ];
    }
}
