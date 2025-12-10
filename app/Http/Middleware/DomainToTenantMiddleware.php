<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Exceptions\CustomException;
use Closure;
use Illuminate\Http\Request;
use Modules\Company\CompanyCore\Models\Domain;
use Modules\WebsiteCMS\WebsiteTheme\Models\WebsiteTheme;
use Symfony\Component\HttpFoundation\Response;

class DomainToTenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Get the X-Domain header
        $domainName = $request->header('X-Domain');

        // If X-Domain header is present
        if ($domainName) {
            // Find the domain in the database
            $domain = Domain::where('domain', $domainName)->first();
            if (!$domain)
            {
                $domain = WebsiteTheme::where('url', $domainName)->firstOrFail();

            }
            // If domain exists, set the X-Tenant header with the company_id
            if ($domain) {
                $request->headers->set('X-Tenant', (string)$domain->company_id);
                if(empty($request->get('company_id')) && $request->method() !='GET'){
                    $request->merge(['company_id'=>(string)$domain->company_id]);
                }
            }
        }

        return $next($request);
    }
}
