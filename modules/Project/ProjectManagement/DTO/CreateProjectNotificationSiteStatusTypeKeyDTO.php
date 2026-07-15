<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\DTO;

final class CreateProjectNotificationSiteStatusTypeKeyDTO
{
    /**
     * @param array<string>|null $options
     */
    public function __construct(
        public readonly string $siteStatusTypeId,
        public readonly string $nameAr,
        public readonly ?string $nameEn = null,
        public readonly string $key = '',
        public readonly string $fieldType = 'text',
        public readonly ?array $options = null,
        public readonly bool $showInSiteStatusUpdates = false,
        public readonly int $sortOrder = 0,
        public readonly bool $isActive = true,
    ) {}

    public function toArray(): array
    {
        return [
            'site_status_type_id' => $this->siteStatusTypeId,
            'name_ar' => $this->nameAr,
            'name_en' => $this->nameEn,
            'key' => $this->key,
            'field_type' => $this->fieldType,
            'options' => $this->options,
            'show_in_site_status_updates' => $this->showInSiteStatusUpdates,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
        ];
    }
}
