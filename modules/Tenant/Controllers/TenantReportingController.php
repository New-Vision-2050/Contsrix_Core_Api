<?php

declare(strict_types=1);

namespace Modules\Tenant\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Tenant\Services\TenantReportingService;

class TenantReportingController extends Controller
{
    /**
     * @var TenantReportingService
     */
    protected $reportingService;

    /**
     * TenantReportingController constructor.
     *
     * @param TenantReportingService $reportingService
     */
    public function __construct(TenantReportingService $reportingService)
    {
        $this->reportingService = $reportingService;
    }

    /**
     * Get user count by tenant
     *
     * @return JsonResponse
     */
    public function getUserCountByTenant(): JsonResponse
    {
        $userCounts = $this->reportingService->getUserCountByTenant();
        
        return Json::success([
            'total_users' => $userCounts->sum('user_count'),
            'tenant_breakdown' => $userCounts,
        ]);
    }

    /**
     * Get comprehensive dashboard statistics across all tenants
     *
     * @return JsonResponse
     */
    public function getDashboardStats(): JsonResponse
    {
        // Get user counts
        $userCounts = $this->reportingService->getUserCountByTenant();
        
        // Get other custom reports as needed
        // For example, you could get order counts, revenue, etc.
        
        // Example of using the custom report functionality
        $activeUserReport = $this->reportingService->getCustomReport(function () {
            // This runs within each tenant's context
            return [
                'active_users' => \Modules\CompanyUser\Models\CompanyUser::where('status', 'active')->count(),
                // Add more metrics as needed
            ];
        });
        
        return Json::success([
            'total_tenants' => $userCounts->count(),
            'total_users' => $userCounts->sum('user_count'),
            'tenant_user_breakdown' => $userCounts,
            'tenant_active_users' => $activeUserReport,
            // Add more aggregated statistics as needed
        ]);
    }
    
    /**
     * Get tenant health report
     * 
     * @return JsonResponse
     */
    public function getTenantHealth(): JsonResponse
    {
        $healthReport = $this->reportingService->getCustomReport(function () {
            // This runs within each tenant's context
            // You could check for database size, last activity, etc.
            return [
                'last_activity' => now()->subHours(rand(1, 48))->toIso8601String(), // Simulated data
                'database_size' => rand(1, 100) . ' MB', // Simulated data
                'status' => 'healthy',
            ];
        });
        
        return Json::success([
            'health_report' => $healthReport,
        ]);
    }
}