<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;

class WidgetCompanyUserPresenter extends AbstractPresenter
{

    private array $total_user_widget;

    private array $total_last_month_user_widget;
    private array $total_active_user_widget;
    private array $total_inactive_user_widget;
    public function __construct(array $total_user_widget , array $total_last_month_user_widget, array $total_active_user_widget, array $total_inactive_user_widget)

    {
        $this->total_user_widget = $total_user_widget;
        $this->total_last_month_user_widget = $total_last_month_user_widget;
        $this->total_active_user_widget = $total_active_user_widget;
        $this->total_inactive_user_widget = $total_inactive_user_widget;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            "total_user_widget"=>$this->total_user_widget,
            "total_last_month_user_widget"=>$this->total_last_month_user_widget,
            "total_active_user_widget"=>$this->total_active_user_widget,
            "total_inactive_user_widget"=>$this->total_inactive_user_widget,
        ];
    }
}
