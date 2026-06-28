<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

class RequestProjectNotificationWorkResumptionDTO
{
    public function __construct(
        public readonly bool $reasonsResolved,
        public readonly bool $safetyNotesReviewed,
        public readonly bool $siteReady,
        public readonly bool $contractorNotified,
        public readonly ?string $notes,
        public readonly ?array $files,
        public readonly ?string $internalProcedureSettingId = null,
    ) {}
}
