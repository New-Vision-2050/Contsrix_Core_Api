<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TenantDomainMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenantDomain = $request->header('Origin'); // Get tenant domain dynamically

        if ($tenantDomain) {
            $tenantDomain = parse_url($tenantDomain, PHP_URL_HOST); // Extract host
            config([
                       'session.domain' => $tenantDomain, // Allow session cookies for this domain
                       'sanctum.stateful' => [$tenantDomain] // Allow authentication from this domain
                   ]);
        }

        return $next($request);
    }
}
