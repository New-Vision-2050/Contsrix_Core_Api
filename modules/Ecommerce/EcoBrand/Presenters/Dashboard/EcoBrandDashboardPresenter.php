<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoBrand\Presenters\Dashboard;

use Modules\Ecommerce\EcoBrand\Models\EcoBrand;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoBrandDashboardPresenter extends AbstractPresenter
{
    private EcoBrand $ecoBrand;

    public function __construct(EcoBrand $ecoBrand)
    {
        $this->ecoBrand = $ecoBrand;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoBrand->id,
            'name' => $this->ecoBrand->name,
            'description' => $this->ecoBrand->description
        ];
    }
}
