<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteIcon\Presenters;

use Modules\WebsiteCMS\CategoryWebsiteCMS\Presenters\CategoryWebsiteCMSPresenter;
use Modules\WebsiteCMS\WebsiteIcon\Models\WebsiteIcon;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WebsiteIconPresenter extends AbstractPresenter
{
    private WebsiteIcon $websiteIcon;

    public function __construct(WebsiteIcon $websiteIcon)
    {
        $this->websiteIcon = $websiteIcon;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->websiteIcon->id,
            'name' => $this->websiteIcon->name,
            "name_ar"=>$this->websiteIcon->getTranslation('name', 'ar'),
            "name_en"=>$this->websiteIcon->getTranslation('name', 'en'),
            "icon"=>$this->websiteIcon->getFirstMediaUrl('icon'),
            "category_website_cms_id"=>$this->websiteIcon->category_website_cms_id,
            "category"=>$this->websiteIcon->category?(new CategoryWebsiteCMSPresenter($this->websiteIcon->category))->getData():null
        ];
    }
}
