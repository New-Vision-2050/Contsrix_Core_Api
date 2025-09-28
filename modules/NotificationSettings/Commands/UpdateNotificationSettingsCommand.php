<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Commands;

use Ramsey\Uuid\UuidInterface;

class UpdateNotificationSettingsCommand
{
    public function __construct(
        private ?string $type = null,
        private ?string $email = null,
        private ?string $phone = null,
        private ?string $reminderType = null,
        private ?string $message = null,
        private ?bool $isActive = null,
        private ?UuidInterface $userId = null,
    ) {
    }



    public function getType(): ?string
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

    public function getReminderType(): ?string
    {
        return $this->reminderType;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public function getUserId(): ?UuidInterface
    {
        return $this->userId;
    }

    public function toArray(): array
    {
        $data = [];

        if ($this->type !== null) {
            $data['type'] = $this->type;
        }

        if ($this->email !== null) {
            $data['email'] = $this->email;
        }

        if ($this->phone !== null) {
            $data['phone'] = $this->phone;
        }

        if ($this->reminderType !== null) {
            $data['reminder_type'] = $this->reminderType;
        }

        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        if ($this->isActive !== null) {
            $data['is_active'] = $this->isActive;
        }

        if ($this->userId !== null) {
            $data['user_id'] = $this->userId->toString();
        }

        return $data;
    }
}
