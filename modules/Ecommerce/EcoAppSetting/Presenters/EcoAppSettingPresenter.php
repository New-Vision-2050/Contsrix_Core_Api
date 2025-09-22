<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Presenters;

use Modules\Ecommerce\EcoAppSetting\Models\EcoAppSetting;
use Modules\Shared\Media\Presenters\MediaPresenter;

class EcoAppSettingPresenter
{
    private EcoAppSetting $ecoAppSetting;

    public function __construct(EcoAppSetting $ecoAppSetting)
    {
        $this->ecoAppSetting = $ecoAppSetting;
    }

    public function getData(): array
    {
        return [
            'id' => $this->ecoAppSetting->id,
            'company_id' => $this->ecoAppSetting->company_id,
            'background_color' => $this->ecoAppSetting->background_color,
            'enable_search' => (int) $this->ecoAppSetting->enable_search,
            'show_logo_on_first_page' => (int) $this->ecoAppSetting->show_logo_on_first_page,
            'show_logo_on_front_page' => (int) $this->ecoAppSetting->show_logo_on_front_page,
            'count_photos' => $this->ecoAppSetting->count_photos,
            'logo' => MediaPresenter::collection( $this->ecoAppSetting->getMedia("eco_logo")),
        ];
    }

    public static function collection($items): array
    {
        return array_map(function ($item) {
            return (new self($item))->getData();
        }, $items);
    }
}
