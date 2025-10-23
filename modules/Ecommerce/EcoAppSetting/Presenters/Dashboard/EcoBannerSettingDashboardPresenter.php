<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Presenters\Dashboard;

use Modules\Ecommerce\EcoAppSetting\Models\EcoBannerSetting;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoBannerSettingDashboardPresenter
{
    private EcoBannerSetting $ecoBannerSetting;

    public function __construct(EcoBannerSetting $ecoBannerSetting)
    {
        $this->ecoBannerSetting = $ecoBannerSetting;
    }

    public function getData(): array
    {
        return [
            'id' => $this->ecoBannerSetting->id,
            'company_id' => $this->ecoBannerSetting->company_id,
            'banner_location' => $this->ecoBannerSetting->banner_location,
            'banner_display_type' => $this->ecoBannerSetting->banner_display_type,
            'banner_count' => $this->ecoBannerSetting->banner_count,
            'enable_banner' => (int) $this->ecoBannerSetting->enable_banner,
            'type_page' => $this->ecoBannerSetting->type_page,
            'banners' => MediaPresenter::collection($this->ecoBannerSetting->getMedia("eco_banners")),
            'created_at' => $this->ecoBannerSetting->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->ecoBannerSetting->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    public static function collection($items): array
    {
        return array_map(function ($item) {
            return (new self($item))->getData();
        }, $items);
    }
}
