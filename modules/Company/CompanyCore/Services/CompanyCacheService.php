<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use App\Exceptions\CustomException;
use Illuminate\Support\Facades\Cache;

/**
 * Service for managing company data caching
 */
class CompanyCacheService
{
    /**
     * Cache duration in minutes
     */
    const CACHE_DURATION = 30;

    /**
     * Cache key prefixes for different entity types
     */
    const CACHE_KEY_COMPANY = 'company_data';
    const CACHE_KEY_LEGAL_DATA = 'company_legal_data';
    const CACHE_KEY_ADDRESS = 'company_address';
    const CACHE_KEY_DOCUMENTS = 'company_official_documents';
    const CACHE_KEY_BRANCHES = 'company_branches';


    public function cacheCompanyData(string $companyId,  ?int $branchId, callable $callback)
    {
        $cacheKey = $this->generateCompanyCacheKey($companyId, $branchId);
        return Cache::remember($cacheKey, now()->addMinutes(self::CACHE_DURATION), $callback);
    }




    public function clearCompanyCache(?string $companyId = null, ?int $branchId = null, ?string $type = null): void
    {

            // Clear main company data cache
            $pattern = $this->generateCompanyCacheKey($companyId, $branchId);
            $this->clearCacheByPattern($pattern);

    }




    private function clearCacheByPattern(string $pattern): void
    {
        Cache::forget($pattern);

    }

    public function generateCompanyCacheKey(string $companyId, ?int $branchId = null, bool $isPattern = false): string
    {
        $key = self::CACHE_KEY_COMPANY . '_' . $companyId;

        if ($branchId) {
            $key .= '_' . $branchId;
        }

        if ($isPattern) {
            $key .= '_*';
        } else {
            // Add user ID to make it user-specific if not a pattern
            $userId = auth()->id() ?? session()->getId();
            $key .= '_' . $userId;
        }

        return $key;
    }



}
