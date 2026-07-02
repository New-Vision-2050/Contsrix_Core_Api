<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Illuminate\Http\UploadedFile;

final class RequestProjectNotificationSiteStatusUpdateDTO
{
    /**
     * @param array<int, UploadedFile>|null $files
     */
    public function __construct(
        public readonly ?string $description = null,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
        public readonly ?float $currentLatitude = null,
        public readonly ?float $currentLongitude = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'description' => $this->description,
        ], static fn ($value) => $value !== null);
    }
}
