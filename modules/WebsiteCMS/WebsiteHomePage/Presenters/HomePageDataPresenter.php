<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\WebsiteCMS\Founder\Presenters\FounderPresenter;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Presenters\WebsiteHomePageSettingPresenter;
use Modules\WebsiteCMS\WebsiteIcon\Presenters\WebsiteIconPresenter;
use Modules\WebsiteCMS\WebsiteOurService\Presenters\WebsiteOurServicePresenter;
use Modules\WebsiteCMS\WebsiteProject\Presenters\WebsiteProjectPresenter;
use Modules\WebsiteCMS\WebsiteService\Presenters\WebsiteServicePresenter;

class HomePageDataPresenter extends AbstractPresenter
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    protected function present(bool $isListing = false): array
    {
        $result = [];

        // Present home page setting
        if (isset($this->data['home_page_setting']) && $this->data['home_page_setting']) {
            $result['home_page_setting'] = (new WebsiteHomePageSettingPresenter($this->data['home_page_setting']))->getData();
        } else {
            $result['home_page_setting'] = null;
        }

        // Present our services
        if (isset($this->data['our_services']) && $this->data['our_services']) {
            $result['our_services'] = (new WebsiteOurServicePresenter($this->data['our_services']))->getData();
        } else {
            $result['our_services'] = null;
        }

        // Present featured projects
        if (isset($this->data['founders']) && $this->data['founders']->isNotEmpty()) {
            $result['founders'] = FounderPresenter::collection($this->data['founders']);
        } else {
            $result['founders'] = [];
        }


        if (isset($this->data['featured_projects']) && $this->data['featured_projects']->isNotEmpty()) {
            $result['featured_projects'] = WebsiteProjectPresenter::collection($this->data['featured_projects']);
        } else {
            $result['featured_projects'] = [];
        }
        if (isset($this->data['approval_icons']) && $this->data['approval_icons']->isNotEmpty()) {
            $result['approval_icons'] = WebsiteIconPresenter::collection($this->data['approval_icons']);
        } else {
            $result['approval_icons'] = [];
        }
        if (isset($this->data['company_icons']) && $this->data['company_icons']->isNotEmpty()) {
            $result['company_icons'] = WebsiteIconPresenter::collection($this->data['company_icons']);
        } else {
            $result['company_icons'] = [];
        }
        if (isset($this->data['certificate_icons']) && $this->data['certificate_icons']->isNotEmpty()) {
            $result['certificate_icons'] = WebsiteIconPresenter::collection($this->data['certificate_icons']);
        } else {
            $result['certificate_icons'] = [];
        }

        if (isset($this->data['website_services']) && $this->data['website_services']->isNotEmpty()) {
            $result['website_services'] = WebsiteServicePresenter::collection($this->data['website_services']);
        } else {
            $result['website_services'] = [];
        }


        return $result;
    }
}
