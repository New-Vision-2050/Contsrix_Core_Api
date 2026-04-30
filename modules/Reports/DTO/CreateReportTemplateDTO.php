<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

final class CreateReportTemplateDTO
{
    public function __construct(
        public readonly array $name,                  // ['ar' => ..., 'en' => ...]
        public readonly ?array $description,          // ['ar' => ..., 'en' => ...] | null
        public readonly ReportWizardConfigDTO $config,
    ) {
    }
}
