<?php

declare(strict_types=1);

namespace Modules\Attendance\DTO;

/**
 * Data Transfer Object for geolocation data
 */
class GeolocationDTO
{
    /**
     * @var float Latitude coordinate
     */
    private float $latitude;

    /**
     * @var float Longitude coordinate
     */
    private float $longitude;

    /**
     * @var string|null Optional address
     */
    private ?string $address;

    /**
     * Constructor
     *
     * @param float $latitude
     * @param float $longitude
     * @param string|null $address
     */
    public function __construct(float $latitude, float $longitude, ?string $address = null)
    {
        $this->validateCoordinates($latitude, $longitude);
        
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->address = $address;
    }

    /**
     * Get latitude
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return $this->latitude;
    }

    /**
     * Get longitude
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return $this->longitude;
    }

    /**
     * Get address
     *
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return self
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if ($this->address !== null) {
            $data['address'] = $this->address;
        }

        return $data;
    }

    /**
     * Create from array
     *
     * @param array $data
     * @return self
     * @throws \InvalidArgumentException
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            throw new \InvalidArgumentException('Latitude and longitude are required');
        }

        return new self(
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['address'] ?? null
        );
    }

    /**
     * Validate coordinates
     *
     * @param float $latitude
     * @param float $longitude
     * @throws \InvalidArgumentException
     */
    private function validateCoordinates(float $latitude, float $longitude): void
    {
        if ($latitude < -90 || $latitude > 90) {
            throw new \InvalidArgumentException('Latitude must be between -90 and 90 degrees');
        }

        if ($longitude < -180 || $longitude > 180) {
            throw new \InvalidArgumentException('Longitude must be between -180 and 180 degrees');
        }
    }
}
