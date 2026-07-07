<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

class ProjectNotificationChartsPresenter
{
    /**
     * Present all chart data in a structured format for the API response.
     *
     * @param  array  $chartsData  Output from ProjectNotificationChartsService::getChartsData()
     * @return array
     */
    public static function presentCharts(array $chartsData): array
    {
        return [
            'status'              => self::presentDimension($chartsData['status'], 'status'),
            'notification_type'   => self::presentDimension($chartsData['notification_type'], 'notification_type'),
            'severity'            => self::presentDimension($chartsData['severity'], 'severity'),
            'work_type'           => self::presentDimension($chartsData['work_type'], 'work_type'),
            'contractor_category' => self::presentDimension($chartsData['contractor_category'], 'contractor_category'),
            'project'             => self::presentDimension($chartsData['project'], 'project'),
            'trend'               => self::presentTrend($chartsData['trend']),
        ];
    }

    /**
     * Present a single dimension's distribution.
     */
    private static function presentDimension(array $dimensionData, string $chartType): array
    {
        return [
            'chart_type' => $chartType,
            'total'      => $dimensionData['total'],
            'data'       => $dimensionData['data'],
        ];
    }

    /**
     * Present the monthly trend chart.
     */
    private static function presentTrend(array $trendData): array
    {
        return [
            'chart_type' => 'trend',
            'total'      => $trendData['total'],
            'data'       => $trendData['data'],
        ];
    }
}
