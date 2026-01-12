<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProjectSetting\Presenters;

use Modules\WebsiteCMS\WebsiteProjectSetting\Models\WebsiteProjectSetting;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteProjectSettingPresenter extends AbstractPresenter
{
    private WebsiteProjectSetting $websiteProjectSetting;

    public function __construct(WebsiteProjectSetting $websiteProjectSetting)
    {
        $this->websiteProjectSetting = $websiteProjectSetting;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteProjectSetting->id,
            'name' => $this->websiteProjectSetting->name,
            "name_ar"=>$this->websiteProjectSetting->getTranslation('name', 'ar'),
            "name_en"=>$this->websiteProjectSetting->getTranslation('name', 'en'),
            "website_projects_count"=>$this->websiteProjectSetting->website_projects_count
        ];
    }
}
