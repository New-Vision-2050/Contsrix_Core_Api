<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Presenters;

use Modules\Company\CompanyCore\Models\Company;
use BasePackage\Shared\Presenters\AbstractPresenter;

class CompanyWidgetPresenter extends AbstractPresenter
{
    private  $total;
    private $active;
    private $completeData;
    private $dataActivate;
    private $totalCalculate;
    private $activeCalculate;
    private $completeDataCalculate;
    private $dataActivateCalculate;

    public function __construct($total,$active,$completeData,$dataActivate,$totalCalculate,$activeCalculate,$completeDataCalculate,$dataActivateCalculate)
    {
        $this->total = $total;
        $this->active = $active;
        $this->completeData = $completeData;
        $this->dataActivate = $dataActivate;
        $this->totalCalculate= $totalCalculate;
        $this->activeCalculate =$activeCalculate;
        $this->completeDataCalculate= $completeDataCalculate;
        $this->dataActivateCalculate = $dataActivateCalculate;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'total' => $this->total,
            'total_calculate' => $this->totalCalculate,
            'active' => $this->active,
            'active_calculate' => $this->activeCalculate,
            'complete_data' => $this->completeData,
            'complete_data_calculate' => $this->completeDataCalculate,
            'data_activate' => $this->dataActivate,
            'data_activate_calculate' => $this->dataActivateCalculate
        ];
    }
}
