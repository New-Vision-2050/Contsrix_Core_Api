<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Dashboard\Presenters;

use Modules\Ecommerce\Dashboard\Models\Dashboard;
use BasePackage\Shared\Presenters\AbstractPresenter;

class DashboardPresenters extends AbstractPresenter
{
    private Dashboard $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->dashboard->id,
            'name' => $this->dashboard->name,
        ];
    }
}
