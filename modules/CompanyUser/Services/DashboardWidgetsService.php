<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardWidgetsService
{
    /**
     * Get all client dashboard widgets data
     */
    public function getWidgetsData(string $companyId, array $dateRange = []): array
    {
        $startDate = $dateRange['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $dateRange['end_date'] ?? Carbon::now();

        return [
            'total_clients' => $this->getTotalClientsWidget($companyId, $startDate, $endDate),
            'clients_added_last_month' => $this->getClientsAddedLastMonthWidget($companyId, $startDate, $endDate),
            'active_clients' => $this->getActiveClientsWidget($companyId, $startDate, $endDate),
            'suspended_clients' => $this->getSuspendedClientsWidget($companyId, $startDate, $endDate),
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
            'total_clients' => $this->getTotalClientsWidget($companyId, $startDate, $endDate),
            'clients_added_last_month' => $this->getClientsAddedLastMonthWidget($companyId, $startDate, $endDate),
            'active_clients' => $this->getActiveClientsWidget($companyId, $startDate, $endDate),
            'suspended_clients' => $this->getSuspendedClientsWidget($companyId, $startDate, $endDate),
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
                'total_clients' => $widgets['total_clients']['count'],
                'clients_added_last_month' => $widgets['clients_added_last_month']['count'],
                'active_clients' => $widgets['active_clients']['count'],
                'suspended_clients' => $widgets['suspended_clients']['count'],
            ],
            'trends' => [
                'total_clients_trend' => $widgets['total_clients']['percentage_change'],
                'clients_added_trend' => $widgets['clients_added_last_month']['percentage_change'],
                'active_clients_trend' => $widgets['active_clients']['percentage_change'],
                'suspended_clients_trend' => $widgets['suspended_clients']['percentage_change'],
            ]
        ];
    }

    /**
     * Get total clients widget data - اجمالي عدد العملاء
     */
    private function getTotalClientsWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period total clients
        $currentClients = $this->getTotalClientsCount($companyId, $endDate);

        // Previous period for comparison
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousClients = $this->getTotalClientsCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentClients, $previousClients);

        return [
            'type' => 'total_clients',
            'title' => __('names.total_clients'),
            'count' => $currentClients,
            'previous_count' => $previousClients,
            'percentage_change' => 100,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get clients added last month widget data - العملاء المضافين اخر شهر
     */
    private function getClientsAddedLastMonthWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period clients added
        $currentAdded = $this->getClientsAddedLastMonthCount($companyId, $startDate, $endDate);

        // Previous period for comparison
        $previousStart = Carbon::parse()->subMonths(1)->startOfMonth();
        $previousEnd = Carbon::parse()->subMonths(1)->endOfMonth();
        $previousAdded = $this->getClientsAddedLastMonthCount($companyId, $previousStart, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentAdded, $previousAdded);

        return [
            'type' => 'clients_added_last_month',
            'title' => __('names.clients_added_last_month'),
            'count' => $currentAdded,
            'previous_count' => $previousAdded,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get active clients widget data - العملاء النشطيين
     */
    private function getActiveClientsWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period active clients
        $currentActive = $this->getActiveClientsCount($companyId, $endDate);

        $total = $this->getTotalClientsCount($companyId, $endDate);


        $percentageChange = $this->calculatePercentageChange( $total,$currentActive);

        return [
            'type' => 'active_clients',
            'title' => __('names.active_clients'),
            'count' => $currentActive,
            'previous_count' => $total,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get suspended clients widget data - العملاء المعلقين
     */
    private function getSuspendedClientsWidget(string $companyId, $startDate, $endDate): array
    {
        // Current period suspended clients
        $currentSuspended = $this->getSuspendedClientsCount($companyId, $endDate);

        // Previous period for comparison
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousSuspended = $this->getSuspendedClientsCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentSuspended, $previousSuspended);

        return [
            'type' => 'suspended_clients',
            'title' => __('names.suspended_clients'),
            'count' => $currentSuspended,
            'previous_count' => $previousSuspended,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get total clients count - from company_users_companies where role = 2
     */
    private function getTotalClientsCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 2) // Client role = 2
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Get clients added last month count
     */
    private function getClientsAddedLastMonthCount(string $companyId, $startDate, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 2) // Client role = 2
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * Get active clients count
     */
    private function getActiveClientsCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 2) // Client role = 2
            ->where('status', 1)
            ->where('created_at', '<=', $endDate)
            ->count();
    }

    /**
     * Get suspended clients count
     */
    private function getSuspendedClientsCount(string $companyId, $endDate): int
    {
        return DB::table('company_users_companies')
            ->where('company_id', $companyId)
            ->where('role', 2) // Client role = 2
            ->where('status', -1)
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
