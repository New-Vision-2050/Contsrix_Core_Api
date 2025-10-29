<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Services;

use Illuminate\Support\Collection;
use Modules\Ecommerce\Dashboard\DTO\CreateDashboardDTO;
use Modules\Ecommerce\Dashboard\Models\Dashboard;
use Modules\Ecommerce\Dashboard\Repositories\DashboardRepository;
use Ramsey\Uuid\UuidInterface;
use App\Traits\HasExportService;
use Modules\Ecommerce\Order\Models\Order;
use Modules\Ecommerce\Warehous\Models\Warehous;
use Modules\Ecommerce\EcoProduct\Models\EcoProduct;

class DashboardCRUDService
{
    use HasExportService;

    public function __construct(
        private DashboardRepository $repository,
    ) {
    }

    public function getMainDashboardData(): array
    {
        $totalOrders = $this->getTotalOrdersCount();
        $totalProducts = $this->getTotalProductsCount();
        $totalWarehouses = $this->getTotalStoresCount();
        
        return [

                [
                    'title' => 'إجمالي عدد الطلبات',
                    'value' => $totalOrders
                ],
                [
                    'title' => 'إجمالي عدد المنتجات',
                    'value' => $totalProducts,

                ],
                [
                    'title' => 'إجمالي عدد المتاجر',
                    'value' => $totalWarehouses,
                ]
            ];
    }

    public function getOrdersChartData(string $period = 'week'): array
    {
        $chartData = $this->getChartDataByPeriod($period);
        
        return [
            'active_tab' => $period,
            'labels' => $chartData['labels'],
            'data' => $chartData['data']
        ];
    }

    private function getWeeklyOrdersData(): array
    {
            $weeklyData = [];
            
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                
                $count = Order::whereDate('created_at', $date)->count();
                $weeklyData[] = $count;
            }
            
            return $weeklyData;
       
    }

    public function getWarehousesTableData(): array
    {
        $warehouses = Warehous::withCount('products')->get();
        
        $warehousesData = [];
        foreach ($warehouses as $warehouse) {
            $warehousesData[] = [
                'name' => $warehouse->name ?? "مخزن - {$warehouse->id}",
                'products_count' => $warehouse->products_count ?? 0
            ];
        }
        
        return $warehousesData;
    }

    private function getStoresTableData(): array
    {
        return $this->getWarehousesTableData();
    }

    private function getTotalOrdersCount(): int
    {
        return Order::count();

    }

    private function getTotalProductsCount(): int
    {
            return EcoProduct::count();

    }

    private function getTotalStoresCount(): int
    {
        return Warehous::count();
    }

    private function getChartDataByPeriod(string $period): array
    {
        switch ($period) {
            case 'week':
                return $this->getWeeklyChartData();
            case 'month':
                return $this->getMonthlyChartData();
            case 'year':
                return $this->getYearlyChartData();
            default:
                return $this->getWeeklyChartData();
        }
    }

    private function getWeeklyChartData(): array
    {
        $data = [];
        $labels = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = Order::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getMonthlyChartData(): array
    {
        $data = [];
        $labels = [];
        
        $startDate = now()->subMonths(11)->startOfMonth();
        
        for ($i = 0; $i < 12; $i++) {
            $date = $startDate->copy()->addMonths($i);
            $labels[] = $date->format('M');
            
            $count = Order::whereYear('created_at', $date->year)
                         ->whereMonth('created_at', $date->month)
                         ->count();
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function getYearlyChartData(): array
    {
        $data = [];
        $labels = [];
        
        for ($i = 4; $i >= 0; $i--) {
            $year = now()->subYears($i)->year;
            $labels[] = (string) $year;
            $count = Order::whereYear('created_at', $year)->count();
            $data[] = $count;
        }
        
        return [
            'labels' => $labels,
            'data' => $data
        ];
    }

    private function calculateMaxValue(array $data): int
    {
        $max = max($data);
        if ($max == 0) return 10;
        
        // Add 20% padding to max value
        $padding = $max * 0.2;
        return (int) ceil($max + $padding);
    }

    private function calculateStepSize(array $data): int
    {
        $max = max($data);
        
        if ($max <= 10) return 1;
        if ($max <= 100) return 10;
        if ($max <= 1000) return 100;
        if ($max <= 10000) return 1000;
        
        return 10000;
    }
}
