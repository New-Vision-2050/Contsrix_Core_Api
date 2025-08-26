<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

class BrokerDashboardWidgetsPresenter
{
    /**
     * Widget translations mapping
     */
    private static array $widgetTranslations = [
        'total_brokers' => [
            'ar' => 'اجمالي عدد الوسطاء',
            'en' => 'Total Brokers'
        ],
        'brokers_added_last_month' => [
            'ar' => 'الوسطاء المضافين اخر شهر',
            'en' => 'Brokers Added Last Month'
        ],
        'active_brokers' => [
            'ar' => 'الوسطاء النشطيين',
            'en' => 'Active Brokers'
        ],
        'suspended_brokers' => [
            'ar' => 'الوسطاء المعلقين',
            'en' => 'Suspended Brokers'
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
                'title' => self::$widgetTranslations['total_brokers'][$locale] ?? self::$widgetTranslations['total_brokers']['en'],
                'total' => $widgetsData['total_brokers']['count'],
                'percentage' => $widgetsData['total_brokers']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['brokers_added_last_month'][$locale] ?? self::$widgetTranslations['brokers_added_last_month']['en'],
                'total' => $widgetsData['brokers_added_last_month']['count'],
                'percentage' => $widgetsData['brokers_added_last_month']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['active_brokers'][$locale] ?? self::$widgetTranslations['active_brokers']['en'],
                'total' => $widgetsData['active_brokers']['count'],
                'percentage' => $widgetsData['active_brokers']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['suspended_brokers'][$locale] ?? self::$widgetTranslations['suspended_brokers']['en'],
                'total' => $widgetsData['suspended_brokers']['count'],
                'percentage' => $widgetsData['suspended_brokers']['percentage_change'],
            ],
        ];
    }

    /**
     * Present specific widget data
     */
    public static function presentWidget(array $widgetData): array
    {
        $locale = app()->getLocale();
        $type = $widgetData['type'];

        return [
            'title' => self::$widgetTranslations[$type][$locale] ?? self::$widgetTranslations[$type]['en'],
            'total' => $widgetData['count'],
            'percentage' => $widgetData['percentage_change'],
            'trend' => $widgetData['trend'],
            'previous_count' => $widgetData['previous_count'],
        ];
    }
}
