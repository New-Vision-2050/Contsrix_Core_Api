<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Presenters\Dashboard;

use BasePackage\Shared\Presenters\AbstractPresenter;

class DashboardReportPresenter extends AbstractPresenter
{
    private array $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'period' => $this->reportData['period'] ?? 'today',
            'generated_at' => now()->toISOString(),
            'summary' => $this->formatSummaryMetrics($this->reportData['summary_metrics'] ?? []),
            'orders' => $this->formatOrdersData($this->reportData['orders_data'] ?? []),
            'sales' => $this->formatSalesAnalytics($this->reportData['sales_analytics'] ?? []),
            'products' => $this->formatProductPerformance($this->reportData['top_products'] ?? []),
            'recent_orders' => $this->formatRecentOrders($this->reportData['recent_orders'] ?? []),
        ];
    }

    private function formatSummaryMetrics(array $metrics): array
    {
        return [
            'total_orders' => $metrics['total_orders'] ?? 0,
            'total_revenue' => number_format($metrics['total_revenue'] ?? 0, 2),
            'average_order_value' => number_format($metrics['average_order_value'] ?? 0, 2),
            'unique_customers' => $metrics['unique_customers'] ?? 0,
            'growth_rates' => $metrics['growth'] ?? [],
        ];
    }

    private function formatOrdersData(array $ordersData): array
    {
        return [
            'chart_data' => $ordersData['chart_data'] ?? [],
            'total_orders' => $ordersData['total_orders'] ?? 0,
            'total_revenue' => number_format($ordersData['total_revenue'] ?? 0, 2),
        ];
    }

    private function formatSalesAnalytics(array $salesData): array
    {
        return [
            'daily_analytics' => $salesData['daily_analytics'] ?? [],
            'totals' => [
                'orders' => $salesData['totals']['orders'] ?? 0,
                'revenue' => number_format($salesData['totals']['revenue'] ?? 0, 2),
                'tax_collected' => number_format($salesData['totals']['tax_collected'] ?? 0, 2),
                'discounts_given' => number_format($salesData['totals']['discounts_given'] ?? 0, 2),
            ]
        ];
    }

    private function formatProductPerformance(array $products): array
    {
        return array_map(function ($product) {
            return [
                'product_name' => $product->product_name ?? '',
                'sku' => $product->sku ?? '',
                'quantity_sold' => (int) ($product->quantity_sold ?? 0),
                'revenue' => number_format($product->revenue ?? 0, 2),
            ];
        }, $products);
    }

    private function formatRecentOrders(array $orders): array
    {
        return array_map(function ($order) {
            return [
                'id' => $order->id ?? '',
                'order_number' => $order->order_number ?? '',
                'customer_name' => $order->customer_name ?? '',
                'total_amount' => number_format($order->total_amount ?? 0, 2),
                'status' => $order->order_status ?? '',
                'created_at' => $order->created_at ?? '',
            ];
        }, $orders);
    }
}
