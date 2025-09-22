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
            
            // Theme Settings
            'background_color' => $this->ecoAppSetting->background_color,
            'enable_search' => (int) $this->ecoAppSetting->enable_search,

            // Front Page Settings
            'show_logo_on_first_page' => (int) $this->ecoAppSetting->show_logo_on_first_page,
            'show_logo_on_front_page' => (int) $this->ecoAppSetting->show_logo_on_front_page,
            'count_photos' => $this->ecoAppSetting->count_photos,
            'logo' => MediaPresenter::collection( $this->ecoAppSetting->getMedia("eco_logo")),
            
            // Product Display Settings
            'product_display_category' => $this->ecoAppSetting->product_display_category,
            'product_display_type' => $this->ecoAppSetting->product_display_type,
            'product_columns_count' => $this->ecoAppSetting->product_columns_count,
            'product_rows_count' => $this->ecoAppSetting->product_rows_count,
            'show_products_in_app' => (int) $this->ecoAppSetting->show_products_in_app,
            
            // Favorites Settings
            'show_favorites_search' => (int) $this->ecoAppSetting->show_favorites_search,
            'show_favorites_delete' => (int) $this->ecoAppSetting->show_favorites_delete,
            'show_favorites_products' => (int) $this->ecoAppSetting->show_favorites_products,
            'favorites_display_type' => $this->ecoAppSetting->favorites_display_type,
            'show_favorites_in_app' => (int) $this->ecoAppSetting->show_favorites_in_app,
        ];
    }

    public static function collection($items): array
    {
        return array_map(function ($item) {
            return (new self($item))->getData();
        }, $items);
    }
}
