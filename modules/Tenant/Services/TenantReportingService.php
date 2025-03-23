<?php

declare(strict_types=1);

namespace Modules\Tenant\Services;

use Illuminate\Support\Collection;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Tenant\Models\Tenant;

class TenantReportingService
{
    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * TenantReportingService constructor.
     *
     * @param TenantService $tenantService
     */
    public function __construct(TenantService $tenantService)
    {
        $this->tenantService = $tenantService;
    }

    /**
     * Get user count across all tenants
     *
     * @return Collection
     */
    public function getUserCountByTenant(): Collection
    {
        $tenants = $this->tenantService->getAllTenants();
        $result = collect();

        foreach ($tenants as $tenant) {
            $userCount = $tenant->run(function () {
                return CompanyUser::count();
            });

            $result->push([
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'company_name' => $tenant->company ? $tenant->company->name : null,
                'user_count' => $userCount,
            ]);
        }

        return $result;
    }

    /**
     * Get total user count across all tenants
     *
     * @return int
     */
    public function getTotalUserCount(): int
    {
        return $this->getUserCountByTenant()->sum('user_count');
    }

    /**
     * Get custom report data across all tenants
     *
     * @param callable $reportCallback Function that runs within each tenant context to gather data
     * @return Collection
     */
    public function getCustomReport(callable $reportCallback): Collection
    {
        $tenants = $this->tenantService->getAllTenants();
        $result = collect();

        foreach ($tenants as $tenant) {
            $reportData = $tenant->run(function () use ($reportCallback) {
                return $reportCallback();
            });

            $result->push([
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'company_name' => $tenant->company ? $tenant->company->name : null,
                'data' => $reportData,
            ]);
        }

        return $result;
    }

    /**
     * Execute a callback function across all tenants
     *
     * @param callable $callback Function to execute within each tenant context
     * @return void
     */
    public function forEachTenant(callable $callback): void
    {
        $tenants = $this->tenantService->getAllTenants();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($callback, $tenant) {
                $callback($tenant);
            });
        }
    }
}