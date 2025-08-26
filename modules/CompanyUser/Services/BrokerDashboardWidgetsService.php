<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BrokerDashboardWidgetsService
{
    /**
     * Get all broker dashboard widgets data
     */
    public function getWidgetsData(string $companyId, array $dateRange = []): array
    {
        $startDate = $dateRange['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $dateRange['end_date'] ?? Carbon::now();

        return [
            'total_brokers' => $this->getTotalBrokersWidget($companyId, $startDate, $endDate),
            'brokers_added_last_month' => $this->getBrokersAddedLastMonthWidget($companyId, $startDate, $endDate),
            'active_brokers' => $this->getActiveBrokersWidget($companyId, $startDate, $endDate),
            'suspended_brokers' => $this->getSuspendedBrokersWidget($companyId, $startDate, $endDate),
        ];
    }

    /**
     * Get specific widget data
     */
    public function getSpecificWidget(string $companyId, string $widgetType, array $dateRange = []): ?array
    {
        $startDate = $dateRange['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $dateRange['end_date'] ?? Carbon::now();

        return match($widgetType) {
            'total_brokers' => $this->getTotalBrokersWidget($companyId, $startDate, $endDate),
            'brokers_added_last_month' => $this->getBrokersAddedLastMonthWidget($companyId, $startDate, $endDate),
            'active_brokers' => $this->getActiveBrokersWidget($companyId, $startDate, $endDate),
            'suspended_brokers' => $this->getSuspendedBrokersWidget($companyId, $startDate, $endDate),
            default => null,
        };
    }

    /**
     * Get widgets summary
     */
    public function getWidgetsSummary(string $companyId, array $dateRange = []): array
    {
        $widgets = $this->getWidgetsData($companyId, $dateRange);

        return [
            'total_widgets' => count($widgets),
            'summary' => [
                'total_brokers' => $widgets['total_brokers']['count'],
                'brokers_added_last_month' => $widgets['brokers_added_last_month']['count'],
                'active_brokers' => $widgets['active_brokers']['count'],
                'suspended_brokers' => $widgets['suspended_brokers']['count'],
            ],
            'trends' => [
                'total_brokers_trend' => $widgets['total_brokers']['percentage_change'],
                'brokers_added_trend' => $widgets['brokers_added_last_month']['percentage_change'],
                'active_brokers_trend' => $widgets['active_brokers']['percentage_change'],
                'suspended_brokers_trend' => $widgets['suspended_brokers']['percentage_change'],
            ]
        ];
    }

    /**
     * Get total brokers widget data - اجمالي عدد الوسطاء
     */
    private function getTotalBrokersWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period total brokers
        $currentBrokers = $this->getTotalBrokersCount($companyId, $endDate);

        // Previous period for comparison
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousBrokers = $this->getTotalBrokersCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentBrokers, $previousBrokers);

        return [
            'type' => 'total_brokers',
            'title' => __('names.total_brokers'),
            'count' => $currentBrokers,
            'previous_count' => $previousBrokers,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get brokers added last month widget data - الوسطاء المضافين اخر شهر
     */
    private function getBrokersAddedLastMonthWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period brokers added
        $currentAdded = $this->getBrokersAddedLastMonthCount($companyId, $startDate, $endDate);

        // Previous period for comparison
        $previousStart = Carbon::parse($startDate)->subMonths(1);
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousAdded = $this->getBrokersAddedLastMonthCount($companyId, $previousStart, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentAdded, $previousAdded);

        return [
            'type' => 'brokers_added_last_month',
            'title' => __('names.brokers_added_last_month'),
            'count' => $currentAdded,
            'previous_count' => $previousAdded,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get active brokers widget data - الوسطاء النشطيين
     */
    private function getActiveBrokersWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period active brokers
        $currentActive = $this->getActiveBrokersCount($companyId, $endDate);

        // Previous period for comparison
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousActive = $this->getActiveBrokersCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentActive, $previousActive);

        return [
            'type' => 'active_brokers',
            'title' => __('names.active_brokers'),
            'count' => $currentActive,
            'previous_count' => $previousActive,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get suspended brokers widget data - الوسطاء المعلقين
     */
    private function getSuspendedBrokersWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period suspended brokers
        $currentSuspended = $this->getSuspendedBrokersCount($companyId, $endDate);

        // Previous period for comparison
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousSuspended = $this->getSuspendedBrokersCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentSuspended, $previousSuspended);

        return [
            'type' => 'suspended_brokers',
            'title' => __('names.suspended_brokers'),
            'count' => $currentSuspended,
            'previous_count' => $previousSuspended,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get total brokers count - from company_users_companies where role = 3
     */
    private function getTotalBrokersCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 3) // Broker role = 3
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Get brokers added last month count
     */
    private function getBrokersAddedLastMonthCount(string $companyId, $startDate, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 3) // Broker role = 3
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get active brokers count
     */
    private function getActiveBrokersCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 3) // Broker role = 3
            ->where('status', 1) // Active status = 1
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Get suspended brokers count
     */
    private function getSuspendedBrokersCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 3) // Broker role = 3
            ->where('status', -1) // Suspended status = -1
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Calculate percentage change between current and previous values
     */
    private function calculatePercentageChange(int $current, int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
