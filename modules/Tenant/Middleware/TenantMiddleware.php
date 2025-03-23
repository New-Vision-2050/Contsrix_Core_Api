<?php

namespace Modules\Tenant\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Services\TenantManager;
use Modules\Tenant\Exceptions\TenantNotFoundExeption;

class TenantMiddleware
{
    /**
     * @var TenantManager
     */
    protected TenantManager $tenantManager;

    /**
     * Create a new middleware instance.
     *
     * @param TenantManager $tenantManager
     */
    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to get tenant ID from different sources
        $tenantId = $this->getTenantId($request);

        if ($tenantId) {
            try {
                $this->tenantManager->setTenantById($tenantId);
            } catch (TenantNotFoundExeption $e) {
                return response()->json(['error' => 'Tenant not found'], 404);
            }
        }

        $response = $next($request);

        // Reset tenant after the request is processed
        $this->tenantManager->resetTenant();

        return $response;
    }

    /**
     * Get the tenant ID from the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function getTenantId(Request $request): ?string
    {
        // Check header
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return $tenantId;
        }

        // Check query parameter
        $tenantId = $request->query('tenant_id');
        if ($tenantId) {
            return $tenantId;
        }

        // Check route parameter
        $tenantId = $request->route('tenant_id');
        if ($tenantId) {
            return $tenantId;
        }

        // Check subdomain (assuming tenant.example.com format)
        $host = $request->getHost();
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $subdomain = $parts[0];
            // Here you would need to look up the tenant by subdomain
            // This is just a placeholder - you'd need to implement the actual lookup
            // $tenant = Company::where('subdomain', $subdomain)->first();
            // return $tenant ? $tenant->id : null;
        }

        return null;
    }
}