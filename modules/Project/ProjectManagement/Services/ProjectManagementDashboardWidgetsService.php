<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Services;

use Carbon\Carbon;
use Modules\Project\ProjectManagement\Repositories\ProjectManagementRepository;

class ProjectManagementDashboardWidgetsService
{
    public function __construct(
        private ProjectManagementRepository $repository
    ) {
    }

    /**
     * Get all project dashboard widgets data
     */
    public function getWidgetsData(string $companyId, array $dateRange = []): array
    {
        $startDate = $dateRange['start_date'] ?? Carbon::now()->startOfMonth();
        $endDate = $dateRange['end_date'] ?? Carbon::now();

        return [
            'total_projects' => $this->getTotalProjectsWidget($companyId, $startDate, $endDate),
            'total_value' => $this->getTotalValueWidget($companyId, $startDate, $endDate),
            'active_projects' => $this->getActiveProjectsWidget($companyId, $startDate, $endDate),
            'inactive_projects' => $this->getInactiveProjectsWidget($companyId, $startDate, $endDate),
        ];
    }

    /**
     * Get total projects widget data - إجمالي المشاريع
     */
    private function getTotalProjectsWidget(string $companyId, $startDate, $endDate): array
    {
        $currentProjects = $this->repository->getTotalProjectsCount($companyId, $endDate);
        
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousProjects = $this->repository->getTotalProjectsCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentProjects, $previousProjects);

        return [
            'type' => 'total_projects',
            'count' => $currentProjects,
            'previous_count' => $previousProjects,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get total value widget data - إجمالي القيمة
     */
    private function getTotalValueWidget(string $companyId, $startDate, $endDate): array
    {
        $currentValue = $this->repository->getTotalProjectsValue($companyId, $endDate);
        
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousValue = $this->repository->getTotalProjectsValue($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentValue, $previousValue);

        return [
            'type' => 'total_value',
            'value' => $currentValue,
            'previous_value' => $previousValue,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get active projects widget data - المشاريع النشطة
     */
    private function getActiveProjectsWidget(string $companyId, $startDate, $endDate): array
    {
        $currentActive = $this->repository->getActiveProjectsCount($companyId, $endDate);
        
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousActive = $this->repository->getActiveProjectsCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentActive, $previousActive);

        return [
            'type' => 'active_projects',
            'count' => $currentActive,
            'previous_count' => $previousActive,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Get inactive projects widget data - المشاريع غير النشطة
     */
    private function getInactiveProjectsWidget(string $companyId, $startDate, $endDate): array
    {
        $currentInactive = $this->repository->getInactiveProjectsCount($companyId, $endDate);
        
        $previousEnd = Carbon::parse($endDate)->subMonths(1);
        $previousInactive = $this->repository->getInactiveProjectsCount($companyId, $previousEnd);

        $percentageChange = $this->calculatePercentageChange($currentInactive, $previousInactive);

        return [
            'type' => 'inactive_projects',
            'count' => $currentInactive,
            'previous_count' => $previousInactive,
            'percentage_change' => $percentageChange,
            'trend' => $percentageChange > 0 ? 'up' : ($percentageChange < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Calculate percentage change between current and previous values
     */
    private function calculatePercentageChange(float|int $current, float|int $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
