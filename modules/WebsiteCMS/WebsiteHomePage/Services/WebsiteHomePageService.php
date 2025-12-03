<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Services;

use Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories\WebsiteHomePageSettingRepository;
use Modules\WebsiteCMS\WebsiteOurService\Repositories\WebsiteOurServiceRepository;
use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;

class WebsiteHomePageService
{
    public function __construct(
        private WebsiteHomePageSettingRepository $homePageSettingRepository,
        private WebsiteOurServiceRepository $ourServiceRepository,
        private WebsiteProjectRepository $projectRepository,
    ) {
    }

    public function getHomePageData(int $limit = 3): array
    {
        return [
            'home_page_setting' => $this->homePageSettingRepository->getCurrentCompanySetting(),
            'our_services' => $this->ourServiceRepository->getCurrentCompanyWebsiteOurService(),
            'featured_projects' => $this->projectRepository->getFeaturedProjects($limit),
        ];
    }
}
