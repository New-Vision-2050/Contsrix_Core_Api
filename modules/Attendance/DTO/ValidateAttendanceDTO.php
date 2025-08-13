<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

class ValidateAttendanceDTO
{
    public function __construct(
        public string $userId,
        public string $clockInTime,
        public ?array $clockInLocation = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null
    ) {
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'clock_in_time' => $this->clockInTime,
            'clock_in_location' => $this->clockInLocation,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
        ];
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getClockInTime(): string
    {
        return $this->clockInTime;
    }

    public function getClockInLocation(): ?array
    {
        return $this->clockInLocation;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }
}
