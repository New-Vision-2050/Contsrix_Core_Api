<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

use Ramsey\Uuid\UuidInterface;

class ClockInDTO
{
    public function __construct(
        public UuidInterface $user_id,
        public UuidInterface $company_id,
        public string $clock_in_time,
        public ?array $location = null,
        public ?string $notes = null,
        public ?string $ip_address = null,
        public ?string $user_agent = null,
    ) {}

    public function toArray(): array
    {
        return [
            'user_id' => $this->user_id,
            'company_id' => $this->company_id,
            'clock_in_time' => $this->clock_in_time,
            'location' => $this->location,
            'notes' => $this->notes,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
        ];
    }

    public function getUserId(): UuidInterface
    {
        return $this->user_id;
    }

    public function getCompanyId(): UuidInterface
    {
        return $this->company_id;
    }

    public function getClockInTime(): string
    {
        return $this->clock_in_time;
    }

    public function getLocation(): ?array
    {
        return $this->location;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getIpAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getUserAgent(): ?string
    {
        return $this->user_agent;
    }
}
