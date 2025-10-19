<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyWidgetPresenter extends AbstractPresenter
{
    private $total;
    private $active;
    private $completeData;
    private $dataActivate;
    private $totalCalculate;
    private $activeCalculate;
    private $completeDataCalculate;
    private $dataActivateCalculate;

    public function __construct(
        $total,
        $active,
        $completeData,
        $dataActivate,
        $totalCalculate,
        $activeCalculate,
        $completeDataCalculate,
        $dataActivateCalculate
    )
    {
        $this->total = $total;
        $this->active = $active;
        $this->completeData = $completeData;
        $this->dataActivate = $dataActivate;
        $this->totalCalculate = $totalCalculate;
        $this->activeCalculate = $activeCalculate;
        $this->completeDataCalculate = $completeDataCalculate;
        $this->dataActivateCalculate = $dataActivateCalculate;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            ['title' => __('lookups.total_companies'), 'code' => 'total_companies', 'total' => $this->total, 'percentage' => $this->totalCalculate],
            ['title' => __('lookups.active_companies'), 'code' => 'active_companies', 'total' => $this->active, 'percentage' => $this->activeCalculate],
            ['title' => __('lookups.incomplete_data_companies'), 'code' => 'incomplete_data_companies', 'total' => $this->completeData, 'percentage' => $this->completeDataCalculate],
            ['title' => __('lookups.nearly_expiring_companies'), 'code' => 'nearly_expiring_companies', 'total' => $this->dataActivate, 'percentage' => $this->dataActivateCalculate],
        ];
    }
}
