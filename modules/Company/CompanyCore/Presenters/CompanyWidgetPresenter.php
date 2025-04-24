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
            ['title'=>'اجمالي الشركات','code'=> 'total_companies' , 'total'=> $this->total,'percentage' => $this->totalCalculate],
            ['title'=>'الشركات الفعالة','code'=> 'active_companies' , 'total'=> $this->active,'percentage' => $this->activeCalculate],
            ['title'=>'شركات غير مكتملة البيانات','code'=> 'complete_data' , 'total'=> $this->completeData,'percentage' => $this->completeDataCalculate],
            //['title'=>'','data_activate' => $this->dataActivate,'percentage' => $this->dataActivateCalculate],
            ['title'=>'شركات قاربت على الانتهاء','code'=> 'nearly_end' , 'total'=> $this->dataActivate,'percentage' => $this->dataActivateCalculate],
        ];
    }
}
