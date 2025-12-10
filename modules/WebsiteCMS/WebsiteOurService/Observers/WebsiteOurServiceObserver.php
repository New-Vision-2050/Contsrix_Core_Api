<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteOurService\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\WebsiteCMS\WebsiteOurService\Models\WebsiteOurService;

class WebsiteOurServiceObserver
{
    private const CACHE_KEY_PREFIX = 'website_home_page_data';

    public function created(WebsiteOurService $service): void
    {
        $this->clearHomePageCache($service->company_id);
    }

    public function updated(WebsiteOurService $service): void
    {
        $this->clearHomePageCache($service->company_id);
    }

    public function deleted(WebsiteOurService $service): void
    {
        $this->clearHomePageCache($service->company_id);
    }

    private function clearHomePageCache(string $companyId): void
    {
        // Clear all cache variations for this company (limits 1-100)
        for ($i = 1; $i <= 100; $i++) {
            $cacheKey = sprintf('%s:%s:limit_%d', self::CACHE_KEY_PREFIX, $companyId, $i);
            Cache::forget($cacheKey);
        }
    }
}
