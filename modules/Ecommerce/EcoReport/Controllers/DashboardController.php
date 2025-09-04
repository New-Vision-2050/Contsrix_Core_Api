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
     * Get warehouse sales data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWarehouseSalesData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);

        return Json::item($data['warehouse_sales']);
    }

    /**
     * Get conversion rates data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getConversionRates(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getDashboardData($period);

        return Json::item($data['conversion_rates']);
    }

    /**
     * Get paginated warehouse sales data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getWarehouseSalesDataPaginated(Request $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $page = (int) $request->get('page', 1);
        $perPage = (int) $request->get('per_page', 10);

        $data = $this->dashboardService->getWarehouseSalesDataPaginated($period, $page, $perPage);

        return Json::item($data);
    }

    /**
     * Get discount sections data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDiscountSectionsData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $data = $this->dashboardService->getDiscountSectionsData($period);

        return Json::item($data);
    }

    /**
     * Get dashboard metrics matching the UI layout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardClient(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $data = $this->dashboardService->getDashboardClient($period);

        return Json::item($data);
    }

    public function getProductsManagement(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $data = $this->dashboardService->getProductsManagementData($period);

        return Json::item($data);
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
