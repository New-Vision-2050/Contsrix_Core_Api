<?php

declare(strict_types=1);

namespace Modules\Reports\DTO;

use Ramsey\Uuid\UuidInterface;

final class UpdateReportTemplateDTO
{
    public function __construct(
        public readonly UuidInterface         $id,
        public readonly array                 $name,
        public readonly ?array                $description,
        public readonly ReportWizardConfigDTO $config,
        public readonly ?bool                 $isActive = null,
    ) {
    }
}
