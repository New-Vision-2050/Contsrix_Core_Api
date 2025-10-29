<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Controllers;

use BasePackage\Shared\Presenters\Json;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\Dashboard\Services\DashboardCRUDService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardCRUDService $dashboardService,
    ) {
    }

    public function getMainDashboard(): JsonResponse
    {
        $dashboardData = $this->dashboardService->getMainDashboardData();
        
        return Json::item($dashboardData, message: 'تم جلب بيانات لوحة التحكم بنجاح');
    }


    public function getOrdersChart(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week'); // week, month, year
        $chartData = $this->dashboardService->getOrdersChartData($period);
        
        return Json::item($chartData, message: 'تم جلب بيانات الرسم البياني بنجاح');
    }

    public function getWarehousesTable(): JsonResponse
    {
        $warehousesData = $this->dashboardService->getWarehousesTableData();
        
        return Json::item($warehousesData, message: 'تم جلب بيانات المخازن بنجاح');
    }
}
