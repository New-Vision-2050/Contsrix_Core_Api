<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class RequestProjectNotificationLocationConfirmationDTO
{
    public function __construct(
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?float $distanceMeters = null,
        public readonly ?bool $isInsideLocation = null,
        public readonly ?string $internalProcedureSettingId = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'distance_meters' => $this->distanceMeters,
            'is_inside_location' => $this->isInsideLocation,
        ], static fn ($value) => $value !== null);
    }
}
