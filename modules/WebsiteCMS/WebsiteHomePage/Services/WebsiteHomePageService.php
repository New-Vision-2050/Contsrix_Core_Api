<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteHomePage\Services;

use Illuminate\Support\Facades\Cache;
use Modules\WebsiteCMS\WebsiteHomePageSetting\Repositories\WebsiteHomePageSettingRepository;
use Modules\WebsiteCMS\WebsiteOurService\Repositories\WebsiteOurServiceRepository;
use Modules\WebsiteCMS\WebsiteProject\Repositories\WebsiteProjectRepository;
use Modules\WebsiteCMS\Founder\Repositories\FounderRepository;
use Modules\WebsiteCMS\WebsiteIcon\Repositories\WebsiteIconRepository;
use Modules\WebsiteCMS\WebsiteAboutUs\Repositories\WebsiteAboutUsRepository;
use Modules\WebsiteCMS\WebsiteService\Repositories\WebsiteServiceRepository;

class WebsiteHomePageService
{
    private const CACHE_KEY_PREFIX = 'website_home_page_data';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private WebsiteHomePageSettingRepository $homePageSettingRepository,
        private WebsiteOurServiceRepository $ourServiceRepository,
        private WebsiteProjectRepository $projectRepository,
        private FounderRepository $founderRepository,
        private WebsiteIconRepository $websiteIconRepository,
        private WebsiteAboutUsRepository $websiteAboutUsRepository,
        private WebsiteHomePageSettingRepository $websiteHomePageSettingRepository,
        private WebsiteServiceRepository $websiteServiceRepository,
    ) {
    }

    public function getHomePageData(int $limit = 3): array
    {
        $companyId = tenant('id');
        $cacheKey = $this->getCacheKey($companyId, $limit);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            $websiteHomePageSetting = $this->websiteHomePageSettingRepository->getCurrentCompanySetting();

            return [
                'home_page_setting' => $this->homePageSettingRepository->getCurrentCompanySetting(),
                'our_services' => $this->ourServiceRepository->getCurrentCompanyWebsiteOurService(),
                'website_services' => $this->websiteServiceRepository->getCurrentCompanyWebsiteServices(),
                'featured_projects' => $this->projectRepository->getFeaturedProjects(),
                'founders' => $this->founderRepository->getCurrentCompanyFounders(),
                'company_icons' => $websiteHomePageSetting && $websiteHomePageSetting->is_companies
                    ? $this->websiteIconRepository->getCompanyIcons()
                    : null,
                'approval_icons' => $websiteHomePageSetting && $websiteHomePageSetting->is_approvals
                    ? $this->websiteIconRepository->getApprovalIcons()
                    : null,
                'certificate_icons' => $websiteHomePageSetting && $websiteHomePageSetting->is_certificates
                    ? $this->websiteIconRepository->getCertificatesIcons()
                    : null,
            ];
        });
    }

    public function clearCache(?int $limit = null): bool
    {
        $companyId = tenant('id');

        if ($limit !== null) {
            // Clear specific cache for this limit
            $cacheKey = $this->getCacheKey($companyId, $limit);
            return Cache::forget($cacheKey);
        }

        // Clear all cache variations for this company (limits 1-100)
        $cleared = true;
        for ($i = 1; $i <= 100; $i++) {
            $cacheKey = $this->getCacheKey($companyId, $i);
            if (Cache::has($cacheKey)) {
                $cleared = Cache::forget($cacheKey) && $cleared;
            }
        }

        return $cleared;
    }

    private function getCacheKey(string $companyId, int $limit): string
    {
        return sprintf('%s:%s:limit_%d', self::CACHE_KEY_PREFIX, $companyId, $limit);
    }
}
