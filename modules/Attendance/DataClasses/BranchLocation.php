<?php

declare(strict_types=1);

namespace Modules\Attendance\DataClasses;

use InvalidArgumentException;

/**
 * Data class representing a branch location with radius enforcement settings
 * 
 * This class encapsulates branch location information including coordinates,
 * radius settings, and validation methods for attendance constraint enforcement.
 */
class BranchLocation
{
    public function __construct(
        public readonly string $name,
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly float $radius,
        public readonly ?string $address = null,
        public readonly ?string $description = null,
        public readonly bool $isActive = true
    ) {
        $this->validate();
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('Branch name is required'),
            latitude: (float)($data['latitude'] ?? throw new InvalidArgumentException('Latitude is required')),
            longitude: (float)($data['longitude'] ?? throw new InvalidArgumentException('Longitude is required')),
            radius: (float)($data['radius'] ?? throw new InvalidArgumentException('Radius is required')),
            address: $data['address'] ?? null,
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true
        );
    }

    /**
     * Convert to array format
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius' => $this->radius,
            'address' => $this->address,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }

    /**
     * Check if a location tracking point is within this branch's radius
     */
    public function containsPoint(LocationTrackingPoint $point): bool
    {
        return $point->isWithinRadius($this->latitude, $this->longitude, $this->radius);
    }

    /**
     * Check if coordinates are within this branch's radius
     */
    public function containsCoordinates(float $lat, float $lon): bool
    {
        $distance = $this->calculateDistance($lat, $lon);
        return ($distance * 1000) <= $this->radius; // Convert km to meters
    }

    /**
     * Calculate distance to coordinates in kilometers
     */
    public function calculateDistance(float $lat, float $lon): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $dLat = deg2rad($lat - $this->latitude);
        $dLon = deg2rad($lon - $this->longitude);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($this->latitude)) * cos(deg2rad($lat)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calculate distance to another branch location in kilometers
     */
    public function distanceTo(BranchLocation $other): float
    {
        return $this->calculateDistance($other->latitude, $other->longitude);
    }

    /**
     * Get radius in different units
     */
    public function getRadiusInKilometers(): float
    {
        return $this->radius / 1000;
    }

    public function getRadiusInFeet(): float
    {
        return $this->radius * 3.28084;
    }

    public function getRadiusInYards(): float
    {
        return $this->radius * 1.09361;
    }

    /**
     * Check if this branch location overlaps with another
     */
    public function overlapsWith(BranchLocation $other): bool
    {
        $distance = $this->distanceTo($other) * 1000; // Convert to meters
        $combinedRadius = $this->radius + $other->radius;
        
        return $distance < $combinedRadius;
    }

    /**
     * Get the center point as a LocationTrackingPoint (for testing/utility)
     */
    public function getCenterPoint(): LocationTrackingPoint
    {
        return LocationTrackingPoint::fromArray([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timestamp' => now()->toDateTimeString(),
            'accuracy' => 1.0,
            'device_id' => 'branch-center',
            'app_version' => '1.0.0',
            'battery_level' => 100,
            'network_type' => 'GPS',
            'location_source' => 'Manual'
        ]);
    }

    /**
     * Create a temporary location exception for this branch
     */
    public function createTemporaryException(string $startTime, string $endTime, string $reason = ''): array
    {
        return [
            'type' => 'temporary_location',
            'start_time' => $startTime,
            'end_time' => $endTime,
            'temporary_location' => [
                'name' => $this->name,
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
                'radius' => $this->radius,
                'reason' => $reason
            ]
        ];
    }

    /**
     * Validate the branch location data
     */
    private function validate(): void
    {
        // Validate name
        if (empty(trim($this->name))) {
            throw new InvalidArgumentException('Branch name cannot be empty');
        }

        // Validate latitude
        if ($this->latitude < -90 || $this->latitude > 90) {
            throw new InvalidArgumentException('Latitude must be between -90 and 90 degrees');
        }

        // Validate longitude
        if ($this->longitude < -180 || $this->longitude > 180) {
            throw new InvalidArgumentException('Longitude must be between -180 and 180 degrees');
        }

        // Validate radius
        if ($this->radius <= 0) {
            throw new InvalidArgumentException('Radius must be a positive number');
        }

        // Validate reasonable radius (not too large)
        if ($this->radius > 10000) { // 10km max radius
            throw new InvalidArgumentException('Radius cannot exceed 10,000 meters');
        }
    }

    /**
     * Factory method for common branch types
     */
    public static function createOfficeLocation(
        string $name,
        float $latitude,
        float $longitude,
        ?string $address = null
    ): self {
        return new self(
            name: $name,
            latitude: $latitude,
            longitude: $longitude,
            radius: 100.0, // 100 meters default for office
            address: $address,
            description: 'Office location'
        );
    }

    public static function createWarehouseLocation(
        string $name,
        float $latitude,
        float $longitude,
        ?string $address = null
    ): self {
        return new self(
            name: $name,
            latitude: $latitude,
            longitude: $longitude,
            radius: 200.0, // 200 meters default for warehouse
            address: $address,
            description: 'Warehouse location'
        );
    }

    public static function createRetailLocation(
        string $name,
        float $latitude,
        float $longitude,
        ?string $address = null
    ): self {
        return new self(
            name: $name,
            latitude: $latitude,
            longitude: $longitude,
            radius: 50.0, // 50 meters default for retail
            address: $address,
            description: 'Retail location'
        );
    }

    public static function createConstructionSite(
        string $name,
        float $latitude,
        float $longitude,
        ?string $address = null
    ): self {
        return new self(
            name: $name,
            latitude: $latitude,
            longitude: $longitude,
            radius: 500.0, // 500 meters default for construction site
            address: $address,
            description: 'Construction site'
        );
    }
}
