<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;

class CompanyWidgetService
{
    public function __construct(
        private CompanyRepository $repository,
    ) {
    }
    public function total():int
    {
        $totalCompany = $this->repository->totalCompany();

        return $totalCompany;
    }
    public function active():int
    {
        $totalCompany = $this->repository->activeCompany();

        return $totalCompany;
    }
    public function completeData():int
    {
        $totalCompany = $this->repository->completeDataCompany();

        return $totalCompany;
    }
    public function dataActivate():int
    {
        $totalCompany = $this->repository->dateActivateCompany();

        return $totalCompany;
    }
    public function totalCalculate(): float
    {
        $thisMonth = $this->repository->totalCompany(Carbon::now());
        $lastMonth = $this->repository->totalCompany(Carbon::now()->subMonth());

        return $this->calculatePercentageChange($thisMonth, $lastMonth);
    }

    public function activeCalculate(): float
    {
        $thisMonth = $this->repository->activeCompany(Carbon::now());
        $lastMonth = $this->repository->activeCompany(Carbon::now()->subMonth());

        return $this->calculatePercentageChange($thisMonth, $lastMonth);
    }

    public function completeDataCalculate(): float
    {
        $thisMonth = $this->repository->completeDataCompany(Carbon::now());
        $lastMonth = $this->repository->completeDataCompany(Carbon::now()->subMonth());

        return $this->calculatePercentageChange($thisMonth, $lastMonth);
    }

    public function dataActivateCalculate(): float
    {
        $thisMonth = $this->repository->dateActivateCompany(Carbon::now());
        $lastMonth = $this->repository->dateActivateCompany(Carbon::now()->subMonth());

        return $this->calculatePercentageChange($thisMonth, $lastMonth);
    }

    private function calculatePercentageChange(int $thisMonth, int $lastMonth): float
    {
        if ($lastMonth == 0) {
            return $thisMonth > 0 ? 100.0 : 0.0;
        }

        return (($thisMonth - $lastMonth) / $lastMonth) * 100;
    }
}
