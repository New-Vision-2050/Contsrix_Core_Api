<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

class DashboardWidgetsPresenter
{
    /**
     * Widget translations mapping
     */
    private static array $widgetTranslations = [
        'total_clients' => [
            'ar' => 'اجمالي عدد العملاء',
            'en' => 'Total Clients'
        ],
        'clients_added_last_month' => [
            'ar' => 'العملاء المضافين اخر شهر',
            'en' => 'Clients Added Last Month'
        ],
        'active_clients' => [
            'ar' => 'العملاء النشطيين',
            'en' => 'Active Clients'
        ],
        'suspended_clients' => [
            'ar' => 'العملاء المعلقين',
            'en' => 'Suspended Clients'
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
                'title' => self::$widgetTranslations['total_clients'][$locale] ?? self::$widgetTranslations['total_clients']['en'],
                'total' => $widgetsData['total_clients']['count'],
                'percentage' => $widgetsData['total_clients']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['clients_added_last_month'][$locale] ?? self::$widgetTranslations['clients_added_last_month']['en'],
                'total' => $widgetsData['clients_added_last_month']['count'],
                'percentage' => $widgetsData['clients_added_last_month']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['active_clients'][$locale] ?? self::$widgetTranslations['active_clients']['en'],
                'total' => $widgetsData['active_clients']['count'],
                'percentage' => $widgetsData['active_clients']['percentage_change'],
            ],
            [
                'title' => self::$widgetTranslations['suspended_clients'][$locale] ?? self::$widgetTranslations['suspended_clients']['en'],
                'total' => $widgetsData['suspended_clients']['count'],
                'percentage' => $widgetsData['suspended_clients']['percentage_change'],
            ],
        ];
    }


}
