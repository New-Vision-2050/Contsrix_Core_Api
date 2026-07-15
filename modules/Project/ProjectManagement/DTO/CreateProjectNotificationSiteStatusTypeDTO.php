<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class CreateProjectNotificationSiteStatusTypeDTO
{
    public function __construct(
        public readonly int $projectTypeId,
        public readonly string $nameAr,
        public readonly ?string $nameEn = null,
        public readonly int $sortOrder = 0,
        public readonly bool $isActive = true,
    ) {}

    public function toArray(): array
    {
        return [
            'project_type_id' => $this->projectTypeId,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];
    }
}
