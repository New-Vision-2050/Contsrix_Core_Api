<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Modules\Ecommerce\EcoReport\Services\Dashboard\EcoReportDashboardService;

class DashboardReportExport implements WithMultipleSheets
{
    public function __construct(
        private EcoReportDashboardService $reportService,
        private string $period = 'today',
        private string $reportType = 'dashboard'
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->reportType === 'dashboard' || $this->reportType === 'sales') {
            $sheets[] = new SummaryMetricsSheet($this->reportService, $this->period);
            $sheets[] = new OrdersDataSheet($this->reportService, $this->period);
        }

        if ($this->reportType === 'dashboard' || $this->reportType === 'products') {
            $sheets[] = new ProductPerformanceSheet($this->reportService, $this->period);
        }

        if ($this->reportType === 'dashboard' || $this->reportType === 'customers') {
            $sheets[] = new CustomerAnalyticsSheet($this->reportService, $this->period);
        }

        return $sheets;
    }
}

class DashboardReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private EcoReportDashboardService $reportService,
        private string $period
    ) {
    }

    public function array(): array
    {
        $metrics = $this->reportService->getSummaryMetrics($this->period);
        
        return [
            [
                $metrics['total_orders'],
                number_format($metrics['total_revenue'], 2),
                number_format($metrics['average_order_value'], 2),
                $metrics['unique_customers'],
                $metrics['growth']['orders'] . '%',
                $metrics['growth']['revenue'] . '%',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'Total Orders',
            'Total Revenue',
            'Average Order Value',
            'Unique Customers',
            'Orders Growth %',
            'Revenue Growth %',
        ];
    }

    public function title(): string
    {
        return 'Summary Metrics';
    }
}

class DashboardReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private EcoReportDashboardService $reportService,
        private string $period
    ) {
    }

    public function array(): array
    {
        $ordersData = $this->reportService->getOrdersData($this->period);
        
        return array_map(function ($item) {
            return [
                $item['date'],
                $item['orders'],
                number_format($item['revenue'], 2),
            ];
        }, $ordersData['chart_data']);
    }

    public function headings(): array
    {
        return [
            'Date',
            'Orders Count',
            'Revenue',
        ];
    }

    public function title(): string
    {
        return 'Daily Orders Data';
    }
}

class DashboardReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private EcoReportDashboardService $reportService,
        private string $period
    ) {
    }

    public function array(): array
    {
        $products = $this->reportService->getProductPerformance($this->period, 50);
        
        return array_map(function ($product) {
            return [
                $product->product_name,
                $product->sku,
                $product->quantity_sold,
                number_format($product->revenue, 2),
            ];
        }, $products);
    }

    public function headings(): array
    {
        return [
            'Product Name',
            'SKU',
            'Quantity Sold',
            'Revenue',
        ];
    }

    public function title(): string
    {
        return 'Product Performance';
    }
}

class DashboardReportExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(
        private EcoReportDashboardService $reportService,
        private string $period
    ) {
    }

    public function array(): array
    {
        $analytics = $this->reportService->getCustomerAnalytics($this->period);
        
        return [
            [
                $analytics['new_customers'],
                $analytics['returning_customers'],
                number_format($analytics['customer_retention_rate'], 2) . '%',
            ]
        ];
    }

    public function headings(): array
    {
        return [
            'New Customers',
            'Returning Customers',
            'Retention Rate %',
        ];
    }

    public function title(): string
    {
        return 'Customer Analytics';
    }
}
