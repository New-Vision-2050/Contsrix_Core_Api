<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Data class representing a single location tracking point
 * 
 * This class encapsulates all the information captured when tracking
 * an employee's location during their work shift, including GPS coordinates,
 * device information, and accuracy metrics.
 */
class LocationTrackingPoint
{
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly Carbon $timestamp,
        public readonly float $accuracy,
        public readonly string $deviceId,
        public readonly string $appVersion,
        public readonly int $batteryLevel,
        public readonly string $networkType = '4G',
        public readonly string $locationSource = 'GPS'
    ) {
        $this->validate();
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float)($data['latitude'] ?? throw new InvalidArgumentException('Latitude is required')),
            longitude: (float)($data['longitude'] ?? throw new InvalidArgumentException('Longitude is required')),
            timestamp: Carbon::parse($data['timestamp'] ?? throw new InvalidArgumentException('Timestamp is required')),
            accuracy: (float)($data['accuracy'] ?? 5.0),
            deviceId: $data['device_id'] ?? throw new InvalidArgumentException('Device ID is required'),
            appVersion: $data['app_version'] ?? '1.0.0',
            batteryLevel: (int)($data['battery_level'] ?? 100),
            networkType: $data['network_type'] ?? '4G',
            locationSource: $data['location_source'] ?? 'GPS'
        );
    }

    /**
     * Convert to array format
     */
    public function toArray(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => $this->timestamp->toDateTimeString(),
            'accuracy' => $this->accuracy,
            'device_id' => $this->deviceId,
            'app_version' => $this->appVersion,
            'battery_level' => $this->batteryLevel,
            'network_type' => $this->networkType,
            'location_source' => $this->locationSource,
        ];
    }

    /**
     * Calculate distance to another point in kilometers
     */
    public function distanceTo(LocationTrackingPoint $other): float
    {
        return $this->calculateDistance(
            $this->latitude,
            $this->longitude,
            $other->latitude,
            $other->longitude
        );
    }

    /**
     * Calculate distance to coordinates in kilometers
     */
    public function distanceToCoordinates(float $lat, float $lon): float
    {
        return $this->calculateDistance($this->latitude, $this->longitude, $lat, $lon);
    }

    /**
     * Check if this point is within radius of given coordinates (in meters)
     */
    public function isWithinRadius(float $lat, float $lon, float $radiusMeters): bool
    {
        $distanceKm = $this->distanceToCoordinates($lat, $lon);
        return ($distanceKm * 1000) <= $radiusMeters;
    }

    /**
     * Check if location accuracy is acceptable (less than threshold)
     */
    public function hasAcceptableAccuracy(float $maxAccuracyMeters = 10.0): bool
    {
        return $this->accuracy <= $maxAccuracyMeters;
    }

    /**
     * Check if battery level is concerning (below threshold)
     */
    public function hasLowBattery(int $threshold = 20): bool
    {
        return $this->batteryLevel < $threshold;
    }

    /**
     * Get time difference from another point in minutes
     */
    public function timeDifferenceInMinutes(LocationTrackingPoint $other): int
    {
        return (int) round(abs($this->timestamp->diffInMinutes($other->timestamp)));
    }

    /**
     * Check if this point was recorded before another point
     */
    public function isBefore(LocationTrackingPoint $other): bool
    {
        return $this->timestamp->isBefore($other->timestamp);
    }

    /**
     * Check if this point was recorded after another point
     */
    public function isAfter(LocationTrackingPoint $other): bool
    {
        return $this->timestamp->isAfter($other->timestamp);
    }

    /**
     * Validate the location tracking point data
     */
    private function validate(): void
    {
        // Validate latitude
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees');
        }

        // Validate longitude
        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees');
        }

        // Validate accuracy
        if ($this->accuracy < 0) {
            throw new InvalidArgumentException('Accuracy must be a positive number');
        }

        // Validate battery level
        if ($this->batteryLevel < 0 || $this->batteryLevel > 100) {
            throw new InvalidArgumentException('Battery level must be between 0 and 100');
        }

        // Validate device ID
        if (empty(trim($this->deviceId))) {
            throw new InvalidArgumentException('Device ID cannot be empty');
        }

        // Validate network type
        $validNetworkTypes = ['2G', '3G', '4G', '5G', 'WiFi', 'Unknown'];
        if (!in_array($this->networkType, $validNetworkTypes)) {
            throw new InvalidArgumentException('Invalid network type. Must be one of: ' . implode(', ', $validNetworkTypes));
        }

        // Validate location source
        $validSources = ['GPS', 'Network', 'Passive', 'Fused', 'Manual'];
        if (!in_array($this->locationSource, $validSources)) {
            throw new InvalidArgumentException('Invalid location source. Must be one of: ' . implode(', ', $validSources));
        }
    }

    /**
     * Calculate distance between two points using Haversine formula
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
