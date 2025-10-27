<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\DTO;

use Ramsey\Uuid\UuidInterface;

class CreateFeatureDTO
{
    public function __construct(
        public UuidInterface $companyId,
        public ?UuidInterface $settingPageId,
        public string $title,
        public string $description,
        public bool $isActive = true,
    ) {
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId->toString(),
            'setting_page_id' => $this->settingPageId?->toString(),
            'title' => $this->title,
            'description' => $this->description,
            'is_active' => $this->isActive,
        ];
    }
}
