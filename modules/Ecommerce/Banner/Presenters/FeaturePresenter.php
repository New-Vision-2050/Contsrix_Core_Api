<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Banner\Presenters;

use Modules\Ecommerce\Banner\Models\Feature;
use BasePackage\Shared\Presenters\AbstractPresenter;

class FeaturePresenter extends AbstractPresenter
{
    private Feature $feature;

    public function __construct(Feature $feature)
    {
        $this->feature = $feature;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->feature->id,
            'company_id' => $this->feature->company_id,
            'setting_page_id' => $this->feature->setting_page_id,
            'title' => $this->feature->title,
            'description' => $this->feature->description,
            'is_active' => (int) $this->feature->is_active,
            'setting_page' => $this->feature->settingPage ? (new SettingPagePresenter($this->feature->settingPage))->getData() : null,
        ];
    }
}
