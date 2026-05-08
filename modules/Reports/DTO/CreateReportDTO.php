<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

final class CreateReportDTO
{
    public function __construct(
        public readonly ReportWizardConfigDTO $config,
        public readonly ?array  $name = null,           // ['ar' => ..., 'en' => ...] – optional override
        public readonly ?string $templateId = null,     // optional template lineage
    ) {
    }
}
