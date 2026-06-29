<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\DTO;

final class EndTaskDTO
{
    /**
     * @param array<int, \Illuminate\Http\UploadedFile>|null $files
     */
    public function __construct(
        public readonly float   $latitude,
        public readonly float   $longitude,
        public readonly ?string $notes = null,
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array  $files = null,
    ) {}
}
