<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

class ProjectManagementDashboardWidgetsPresenter
{
    /**
     * Widget translations mapping
     */
    private static array $widgetTranslations = [
        'total_projects' => [
            'ar' => 'إجمالي المشاريع',
            'en' => 'Total Projects'
        ],
        'total_value' => [
            'ar' => 'إجمالي القيمة',
            'en' => 'Total Value'
        ],
        'active_projects' => [
            'ar' => 'المشاريع النشطة',
            'en' => 'Active Projects'
        ],
        'inactive_projects' => [
            'ar' => 'المشاريع غير النشطة',
            'en' => 'Inactive Projects'
        ]
    ];

    /**
     * Present all widgets data
     */
    public static function presentWidgets(array $widgetsData): array
    {
        $locale = app()->getLocale();

        return [
            [
                'title' => self::$widgetTranslations['total_projects'][$locale] ?? self::$widgetTranslations['total_projects']['en'],
                'total' => $widgetsData['total_projects']['count'],
                'percentage' => $widgetsData['total_projects']['percentage_change'],
                'trend' => $widgetsData['total_projects']['trend'],
            ],
            [
                'title' => self::$widgetTranslations['total_value'][$locale] ?? self::$widgetTranslations['total_value']['en'],
                'total' => $widgetsData['total_value']['value'],
                'percentage' => $widgetsData['total_value']['percentage_change'],
                'trend' => $widgetsData['total_value']['trend'],
            ],
            [
                'title' => self::$widgetTranslations['active_projects'][$locale] ?? self::$widgetTranslations['active_projects']['en'],
                'total' => $widgetsData['active_projects']['count'],
                'percentage' => $widgetsData['active_projects']['percentage_change'],
                'trend' => $widgetsData['active_projects']['trend'],
            ],
            [
                'title' => self::$widgetTranslations['inactive_projects'][$locale] ?? self::$widgetTranslations['inactive_projects']['en'],
                'total' => $widgetsData['inactive_projects']['count'],
                'percentage' => $widgetsData['inactive_projects']['percentage_change'],
                'trend' => $widgetsData['inactive_projects']['trend'],
            ],
        ];
    }
}
