<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteProject\Observers;

use Illuminate\Support\Facades\Cache;
use Modules\WebsiteCMS\WebsiteProject\Models\WebsiteProject;

class WebsiteProjectObserver
{
    private const CACHE_KEY_PREFIX = 'website_home_page_data';

    public function created(WebsiteProject $project): void
    {
        $this->clearHomePageCache($project->company_id);
    }

    public function updated(WebsiteProject $project): void
    {
        $this->clearHomePageCache($project->company_id);
    }

    public function deleted(WebsiteProject $project): void
    {
        $this->clearHomePageCache($project->company_id);
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
