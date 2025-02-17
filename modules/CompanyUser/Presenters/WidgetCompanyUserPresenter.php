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

    protected function present(bool $isListing = false): array
    {
        return [
            "total_user_widget" => $this->totalUserWidget,
            "total_last_month_user_widget" => $this->totalLastMonthUserWidget,
            "total_active_user_widget" => $this->totalActiveUserWidget,
            "total_inactive_user_widget" => $this->totalInactiveUserWidget,
        ];
    }
}
