<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Data class representing a temporary location exception
 * 
 * This class handles temporary location exceptions that allow employees
 * to work from alternative locations for specific time periods without
 * triggering radius enforcement violations.
 */
class TemporaryLocationException
{
    public function __construct(
        public readonly string $type,
        public readonly Carbon $startTime,
        public readonly Carbon $endTime,
        public readonly BranchLocation $temporaryLocation,
        public readonly ?string $reason = null,
        public readonly ?string $approvedBy = null,
        public readonly bool $isActive = true
    ) {
        $this->validate();
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $temporaryLocationData = $data['temporary_location'] ?? throw new InvalidArgumentException('Temporary location data is required');
        
        return new self(
            type: $data['type'] ?? 'temporary_location',
            startTime: Carbon::parse($data['start_time'] ?? throw new InvalidArgumentException('Start time is required')),
            endTime: Carbon::parse($data['end_time'] ?? throw new InvalidArgumentException('End time is required')),
            temporaryLocation: BranchLocation::fromArray($temporaryLocationData),
            reason: $data['reason'] ?? null,
            approvedBy: $data['approved_by'] ?? null,
            isActive: $data['is_active'] ?? true
        );
    }

    /**
     * Convert to array format
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'start_time' => $this->startTime->toDateTimeString(),
            'end_time' => $this->endTime->toDateTimeString(),
            'temporary_location' => $this->temporaryLocation->toArray(),
            'reason' => $this->reason,
            'approved_by' => $this->approvedBy,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * Check if the exception is currently active
     */
    public function isCurrentlyActive(?Carbon $checkTime = null): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $checkTime = $checkTime ?? now();
        return $checkTime->between($this->startTime, $this->endTime);
    }

    /**
     * Check if a tracking point is covered by this exception
     */
    public function coversTrackingPoint(LocationTrackingPoint $point): bool
    {
        if (!$this->isCurrentlyActive($point->timestamp)) {
            return false;
        }

        return $this->temporaryLocation->containsPoint($point);
    }

    /**
     * Check if coordinates are covered by this exception at a specific time
     */
    public function coversCoordinatesAtTime(float $lat, float $lon, Carbon $time): bool
    {
        if (!$this->isCurrentlyActive($time)) {
            return false;
        }

        return $this->temporaryLocation->containsCoordinates($lat, $lon);
    }

    /**
     * Get the duration of the exception in minutes
     */
    public function getDurationInMinutes(): int
    {
        return (int) round($this->startTime->diffInMinutes($this->endTime));
    }

    /**
     * Get the duration of the exception in hours
     */
    public function getDurationInHours(): float
    {
        return $this->getDurationInMinutes() / 60;
    }

    /**
     * Check if the exception has expired
     */
    public function hasExpired(?Carbon $checkTime = null): bool
    {
        $checkTime = $checkTime ?? now();
        return $checkTime->isAfter($this->endTime);
    }

    /**
     * Check if the exception is upcoming
     */
    public function isUpcoming(?Carbon $checkTime = null): bool
    {
        $checkTime = $checkTime ?? now();
        return $checkTime->isBefore($this->startTime);
    }

    /**
     * Get remaining time until exception starts (in minutes)
     */
    public function getMinutesUntilStart(?Carbon $checkTime = null): int
    {
        $checkTime = $checkTime ?? now();
        
        if ($checkTime->isAfter($this->startTime)) {
            return 0;
        }

        return (int) round($checkTime->diffInMinutes($this->startTime));
    }

    /**
     * Get remaining time until exception ends (in minutes)
     */
    public function getMinutesUntilEnd(?Carbon $checkTime = null): int
    {
        $checkTime = $checkTime ?? now();
        
        if ($checkTime->isAfter($this->endTime)) {
            return 0;
        }

        return (int) round($checkTime->diffInMinutes($this->endTime));
    }

    /**
     * Check if this exception overlaps with another exception
     */
    public function overlapsWith(TemporaryLocationException $other): bool
    {
        return $this->startTime->isBefore($other->endTime) && 
               $this->endTime->isAfter($other->startTime);
    }

    /**
     * Extend the exception end time
     */
    public function extendBy(int $minutes): self
    {
        return new self(
            type: $this->type,
            startTime: $this->startTime,
            endTime: $this->endTime->copy()->addMinutes($minutes),
            temporaryLocation: $this->temporaryLocation,
            reason: $this->reason,
            approvedBy: $this->approvedBy,
            isActive: $this->isActive
        );
    }

    /**
     * Deactivate the exception
     */
    public function deactivate(): self
    {
        return new self(
            type: $this->type,
            startTime: $this->startTime,
            endTime: $this->endTime,
            temporaryLocation: $this->temporaryLocation,
            reason: $this->reason,
            approvedBy: $this->approvedBy,
            isActive: false
        );
    }

    /**
     * Validate the exception data
     */
    private function validate(): void
    {
        // Validate type
        $validTypes = ['temporary_location', 'client_site', 'field_work', 'emergency', 'maintenance'];
        if (!in_array($this->type, $validTypes)) {
            throw new InvalidArgumentException('Invalid exception type. Must be one of: ' . implode(', ', $validTypes));
        }

        // Validate time range
        if ($this->startTime->isAfter($this->endTime)) {
            throw new InvalidArgumentException('Start time must be before end time');
        }

        // Validate duration (not too long)
        $maxDurationHours = 24 * 7; // 1 week max
        if ($this->getDurationInHours() > $maxDurationHours) {
            throw new InvalidArgumentException('Exception duration cannot exceed 1 week');
        }
    }

    /**
     * Factory methods for common exception types
     */
    public static function createClientSiteException(
        Carbon $startTime,
        Carbon $endTime,
        BranchLocation $clientLocation,
        string $reason = 'Client site visit'
    ): self {
        return new self(
            type: 'client_site',
            startTime: $startTime,
            endTime: $endTime,
            temporaryLocation: $clientLocation,
            reason: $reason
        );
    }

    public static function createFieldWorkException(
        Carbon $startTime,
        Carbon $endTime,
        BranchLocation $fieldLocation,
        string $reason = 'Field work assignment'
    ): self {
        return new self(
            type: 'field_work',
            startTime: $startTime,
            endTime: $endTime,
            temporaryLocation: $fieldLocation,
            reason: $reason
        );
    }

    public static function createEmergencyException(
        Carbon $startTime,
        Carbon $endTime,
        BranchLocation $emergencyLocation,
        string $reason = 'Emergency response'
    ): self {
        return new self(
            type: 'emergency',
            startTime: $startTime,
            endTime: $endTime,
            temporaryLocation: $emergencyLocation,
            reason: $reason
        );
    }

    public static function createMaintenanceException(
        Carbon $startTime,
        Carbon $endTime,
        BranchLocation $maintenanceLocation,
        string $reason = 'Maintenance work'
    ): self {
        return new self(
            type: 'maintenance',
            startTime: $startTime,
            endTime: $endTime,
            temporaryLocation: $maintenanceLocation,
            reason: $reason
        );
    }
}
