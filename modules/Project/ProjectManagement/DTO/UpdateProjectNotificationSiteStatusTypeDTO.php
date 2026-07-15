<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class UpdateProjectNotificationSiteStatusTypeDTO
{
    public function __construct(
        public readonly ?string $nameAr = null,
        public readonly ?string $nameEn = null,
        public readonly ?int $sortOrder = null,
        public readonly ?bool $isActive = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ], static fn ($value) => $value !== null);
    }
}
