<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Controllers;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Ecommerce\EcoReport\Services\DashboardService;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {
    }

    /**
     * Get complete dashboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data);
    }

    /**
     * Get summary metrics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSummaryMetrics(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data['summary']);
    }

    /**
     * Get orders data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data['orders']);
    }

    /**
     * Get shipping methods data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getShippingMethods(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data['shipping']);
    }

    /**
     * Get payment methods data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getPaymentMethods(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data['payment']);
    }

    /**
     * Get order status summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderStatusSummary(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item($data['order_status']);
    }
    
    /**
     * Get average processing time
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAverageProcessingTime(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item([
            'value' => 5,
            'unit' => 'دقائق',
            'label' => 'متوسط وقت تجهيز الطلب'
        ]);
    }
    
    /**
     * Get average delivery time
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAverageDeliveryTime(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);
        
        return Json::item([
            'value' => 5,
            'unit' => 'دقائق',
            'label' => 'متوسط وقت توصيل الطلب'
        ]);
    }
    
    /**
     * Clear dashboard cache
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $periods = ['today', 'week', 'month', 'year'];
        
        foreach ($periods as $period) {
            Cache::forget("dashboard_data_{$period}");
        }
        
        return Json::success('Cache cleared successfully');
    }
}
