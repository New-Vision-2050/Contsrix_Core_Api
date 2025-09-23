<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Modules\Ecommerce\EcoReport\Services\Dashboard\EcoReportDashboardService;
use Modules\Ecommerce\EcoReport\Requests\Dashboard\GetDashboardReportRequest;
use Modules\Ecommerce\EcoReport\Requests\Dashboard\GetMetricsReportRequest;
use Modules\Ecommerce\EcoReport\Requests\Dashboard\ExportReportRequest;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Ecommerce\EcoReport\Exports\DashboardReportExport;
use Modules\Ecommerce\EcoReport\Requests\Dashboard\GetEcoReportDashboardRequest;

class EcoReportDashboardController extends Controller
{
    public function __construct(
        private EcoReportDashboardService $dashboardService
    ) {
    }

    /**
     * Get complete dashboard data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboard(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getSummaryMetrics(GetEcoReportDashboardRequest $request): JsonResponse
    {
        $period = $request->get('period', 'today');
        $data = $this->dashboardService->getSummaryMetrics($period);

        return Json::item($data);
    }

    /**
     * Get orders data
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrdersData(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getShippingMethods(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getPaymentMethods(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getOrderStatusSummary(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getAverageProcessingTime(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getAverageDeliveryTime(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getWarehouseSalesData(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getConversionRates(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getWarehouseSalesDataPaginated(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getDiscountSectionsData(GetEcoReportDashboardRequest $request): JsonResponse
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
    public function getDashboardClient(GetEcoReportDashboardRequest $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $data = $this->dashboardService->getDashboardClient($period);

        return Json::item($data);
    }

    public function getProductsManagement(GetEcoReportDashboardRequest $request): JsonResponse
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
