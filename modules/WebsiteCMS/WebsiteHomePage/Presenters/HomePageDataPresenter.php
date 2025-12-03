<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Presenters\WebsiteHomePageSettingPresenter;
use Modules\WebsiteCMS\WebsiteOurService\Presenters\WebsiteOurServicePresenter;
use Modules\WebsiteCMS\WebsiteProject\Presenters\WebsiteProjectPresenter;

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
        if (isset($this->data['featured_projects']) && $this->data['featured_projects']->isNotEmpty()) {
            $result['featured_projects'] = WebsiteProjectPresenter::collection($this->data['featured_projects']);
        } else {
            $result['featured_projects'] = [];
        }

        return $result;
    }
}
