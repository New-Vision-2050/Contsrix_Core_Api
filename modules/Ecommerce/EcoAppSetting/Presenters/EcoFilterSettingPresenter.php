<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoAppSetting\Presenters;

use Modules\Ecommerce\EcoAppSetting\Models\EcoFilterSetting;

class EcoFilterSettingPresenter
{
    private EcoFilterSetting $ecoFilterSetting;

    public function __construct(EcoFilterSetting $ecoFilterSetting)
    {
        $this->ecoFilterSetting = $ecoFilterSetting;
    }

    public function getData(): array
    {
        return [
            'id' => $this->ecoFilterSetting->id,
            'filter_name' => $this->ecoFilterSetting->filter_name,
            'filter_key' => $this->ecoFilterSetting->filter_key,
        ];
    }

    public static function collection($items): array
    {
        return $items->map(function ($item) {
            return (new self($item))->getData();
        })->toArray();
    }
}
