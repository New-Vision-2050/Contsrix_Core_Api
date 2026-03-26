<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WidgetCompanyUserPresenter extends AbstractPresenter
{

    private array $totalUserWidget;
    private array $totalLastMonthUserWidget;
    private array $totalActiveUserWidget;
    private array $totalInactiveUserWidget;

    public function __construct(array $totalUserWidget, array $totalLastMonthUserWidget, array $totalActiveUserWidget, array $totalInactiveUserWidget)

    {
        $this->totalUserWidget = $totalUserWidget;
        $this->totalLastMonthUserWidget = $totalLastMonthUserWidget;
        $this->totalActiveUserWidget = $totalActiveUserWidget;
        $this->totalInactiveUserWidget = $totalInactiveUserWidget;
    }

    private static array $widgetTranslations = [
        'total_user_widget' => [
            'ar' => 'اجمالي عدد المستخدمين',
            'en' => 'Total Users',
        ],
        'total_last_month_user_widget' => [
            'ar' => 'المستخدمين المضافين اخر شهر',
            'en' => 'Users Added Last Month',
        ],
        'total_active_user_widget' => [
            'ar' => 'المستخدمين النشيطين',
            'en' => 'Active Users',
        ],
        'total_inactive_user_widget' => [
            'ar' => 'المستخدمين المعلقين',
            'en' => 'Inactive Users',
        ],
    ];

    protected function present(bool $isListing = false): array
    {
        $locale = app()->getLocale();

        return [
            array_merge(['title' => self::$widgetTranslations['total_user_widget'][$locale] ?? self::$widgetTranslations['total_user_widget']['en'], 'code' => 'total_user_widget'], $this->totalUserWidget),
            array_merge(['title' => self::$widgetTranslations['total_last_month_user_widget'][$locale] ?? self::$widgetTranslations['total_last_month_user_widget']['en'], 'code' => 'total_last_month_user_widget'], $this->totalLastMonthUserWidget),
            array_merge(['title' => self::$widgetTranslations['total_active_user_widget'][$locale] ?? self::$widgetTranslations['total_active_user_widget']['en'], 'code' => 'total_active_user_widget'], $this->totalActiveUserWidget),
            array_merge(['title' => self::$widgetTranslations['total_inactive_user_widget'][$locale] ?? self::$widgetTranslations['total_inactive_user_widget']['en'], 'code' => 'total_inactive_user_widget'], $this->totalInactiveUserWidget),
        ];
    }
}
