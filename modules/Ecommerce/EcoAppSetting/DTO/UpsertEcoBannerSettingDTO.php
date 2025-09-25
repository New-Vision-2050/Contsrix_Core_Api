<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\DTO;

use Ramsey\Uuid\UuidInterface;

class UpsertEcoBannerSettingDTO
{
    public function __construct(
        public readonly UuidInterface $company_id,
        public readonly string $banner_location = 'top',
        public readonly string $banner_display_type = 'slider',
        public readonly int $banner_count = 1,
        public readonly int $enable_banner = 1,
        public readonly ?string $type_page = null,
        private mixed $banners = null,
    ) {}

    public function getBanners(): mixed {
        return $this->banners;
    }

    public function toArray(): array
    {
        return [
            'company_id' => $this->company_id->toString(),
            'banner_location' => $this->banner_location,
            'banner_display_type' => $this->banner_display_type,
            'banner_count' => $this->banner_count,
            'enable_banner' => $this->enable_banner,
            'type_page' => $this->type_page,
        ];
    }
}
