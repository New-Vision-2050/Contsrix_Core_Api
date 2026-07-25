<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

class AttachmentRequestChartsPresenter
{
    public static function presentCharts(array $chartsData): array
    {
        return [
            'attachment_requests' => [
                'summary'         => $chartsData['attachment_requests']['summary'],
                'status'          => self::presentDimension($chartsData['attachment_requests']['status'], 'status'),
                'direction'       => self::presentDimension($chartsData['attachment_requests']['direction'], 'direction'),
                'procedure'       => self::presentDimension($chartsData['attachment_requests']['procedure'], 'procedure'),
                'attachment_type' => self::presentDimension($chartsData['attachment_requests']['attachment_type'], 'attachment_type'),
                'item_status'     => self::presentDimension($chartsData['attachment_requests']['item_status'], 'item_status'),
                'file_type'       => self::presentDimension($chartsData['attachment_requests']['file_type'], 'file_type'),
                'project'         => self::presentDimension($chartsData['attachment_requests']['project'], 'project'),
                'trend'           => self::presentDimension($chartsData['attachment_requests']['trend'], 'trend'),
            ],
            'requirement_submissions' => [
                'summary'     => $chartsData['requirement_submissions']['summary'],
                'status'      => self::presentDimension($chartsData['requirement_submissions']['status'], 'status'),
                'direction'   => self::presentDimension($chartsData['requirement_submissions']['direction'], 'direction'),
                'procedure'   => self::presentDimension($chartsData['requirement_submissions']['procedure'], 'procedure'),
                'requirement' => self::presentDimension($chartsData['requirement_submissions']['requirement'], 'requirement'),
                'file_type'   => self::presentDimension($chartsData['requirement_submissions']['file_type'], 'file_type'),
                'project'     => self::presentDimension($chartsData['requirement_submissions']['project'], 'project'),
                'trend'       => self::presentDimension($chartsData['requirement_submissions']['trend'], 'trend'),
            ],
        ];
    }

    private static function presentDimension(array $dimensionData, string $chartType): array
    {
        return [
            'chart_type' => $chartType,
            'total'      => $dimensionData['total'],
            'data'       => $dimensionData['data'],
        ];
    }
}
