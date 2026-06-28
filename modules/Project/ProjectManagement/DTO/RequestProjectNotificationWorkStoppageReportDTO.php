<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

use Illuminate\Http\UploadedFile;

final class RequestProjectNotificationWorkStoppageReportDTO
{
    /**
     * @param array<int, array{reason_id?: string|null, notes?: string|null, sort_order?: int|null}> $reasons
     * @param array<int, UploadedFile>|null $files
     */
    public function __construct(
        public readonly ?string $otherNotes = null,
        public readonly array $reasons = [],
        public readonly ?string $internalProcedureSettingId = null,
        public readonly ?array $files = null,
    ) {}
}
