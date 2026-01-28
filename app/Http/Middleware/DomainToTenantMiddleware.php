<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\CustomException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use Symfony\Component\HttpFoundation\Response;

class DomainToTenantMiddleware
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Get the X-Domain header
        $domainName = $request->header('X-Domain');

        // If X-Domain header is present
        if ($domainName) {
            // Use cache to avoid database query on every request
            $domain = Domain::where('domain', $domainName)->first();
            if (!$domain) {
                $domain = WebsiteTheme::where('url', $domainName)->first();
            }
            $companyId = $domain?->company_id;
            // If domain exists, set the X-Tenant header with the company_id
            if ($companyId) {
                $request->headers->set('X-Tenant', (string)$companyId);
                if (empty($request->get('company_id')) && $request->method() != 'GET') {
                    $request->merge(['company_id' => (string)$companyId]);
                }
            }
        }

        return $next($request);
    }
}
