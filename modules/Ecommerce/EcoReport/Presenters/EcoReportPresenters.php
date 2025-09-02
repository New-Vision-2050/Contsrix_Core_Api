<?php

declare(strict_types=1);

namespace Modules\Ecommerce\EcoReport\Presenters;

use Modules\Ecommerce\EcoReport\Models\EcoReport;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EcoReportPresenters extends AbstractPresenter
{
    private EcoReport $ecoReport;

    public function __construct(EcoReport $ecoReport)
    {
        $this->ecoReport = $ecoReport;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->ecoReport->id,
            'name' => $this->ecoReport->name,
        ];
    }
}
