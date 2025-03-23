<?php

declare(strict_types=1);

namespace Modules\Tenant\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Presenters\TenantPresenter;
use Modules\Tenant\Services\TenantService;

class TenantController extends Controller
{
    public function __construct(
        private TenantService $tenantService,
    ) {
    }

    /**
     * Get a list of all tenants
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $tenants = $this->tenantService->getAllTenants();
        
        return Json::items(TenantPresenter::collection($tenants));
    }

    /**
     * Create a new tenant for a company
     *
     * @param string $companyId
     * @return JsonResponse
     */
    public function store(string $companyId): JsonResponse
    {
        $company = Company::findOrFail($companyId);
        
        // Check if tenant already exists for this company
        $existingTenant = $this->tenantService->getTenantByCompanyId($companyId);
        if ($existingTenant) {
            return Json::error('Tenant already exists for this company', 409);
        }
        
        $tenant = $this->tenantService->createTenant($company);
        
        return Json::item(new TenantPresenter($tenant));
    }

    /**
     * Get tenant details
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $tenant = Tenant::with('company')->findOrFail($id);
        
        return Json::item(new TenantPresenter($tenant));
    }

    /**
     * Delete a tenant
     *
     * @param string $id
     * @return JsonResponse
     */
    public function destroy(string $id): JsonResponse
    {
        $tenant = Tenant::findOrFail($id);
        
        $this->tenantService->deleteTenant($tenant);
        
        return Json::deleted();
    }

    /**
     * Get statistics across all tenants
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $tenants = Tenant::all();
        $stats = [];
        
        // For each tenant, run a query to get statistics
        foreach ($tenants as $tenant) {
            $tenant->run(function () use (&$stats, $tenant) {
                // Get user count for this tenant
                $userCount = \Modules\CompanyUser\Models\CompanyUser::count();
                
                // Add to stats array
                $stats[] = [
                    'tenant_id' => $tenant->id,
                    'tenant_name' => $tenant->name,
                    'company_name' => $tenant->company ? $tenant->company->name : null,
                    'user_count' => $userCount,
                    // Add more statistics as needed
                ];
            });
        }
        
        return Json::success([
            'total_tenants' => count($tenants),
            'tenant_stats' => $stats,
        ]);
    }
}
