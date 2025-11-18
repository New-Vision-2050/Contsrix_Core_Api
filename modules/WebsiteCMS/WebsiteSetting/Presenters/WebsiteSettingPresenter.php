<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteSetting\Presenters;

use Modules\WebsiteCMS\WebsiteSetting\Models\WebsiteSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteSettingPresenter extends AbstractPresenter
{
    private ?WebsiteSetting $websiteSetting;

    public function __construct(?WebsiteSetting $websiteSetting)
    {
        $this->websiteSetting = $websiteSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $logoUrl = null;
        if ($this->websiteSetting?->getFirstMedia('logo')) {
            $logoUrl = $this->websiteSetting?->getFirstMediaUrl('logo');
        }

        return [
            'id' => $this->websiteSetting?->id,
            'main_color' => $this->websiteSetting?->main_color,
            'second_color' => $this->websiteSetting?->second_color,
            'background_color' => $this->websiteSetting?->background_color,
            'logo' => $logoUrl,
            'website_address' => $this->websiteSetting?->website_address,
            'company_id' => $this->websiteSetting?->company_id,
            'created_at' => $this->websiteSetting?->created_at,
            'updated_at' => $this->websiteSetting?->updated_at,
        ];
    }
}
