<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Presenters\CompanyWidgetPresenter;


class CompanyWidgetService
{
    protected $repository;

    public function __construct(CompanyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    public function getCompanyStatistics()
    {
        $now = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        $currentStats = $this->repository->getCompanyStatistics($now);
        $previousStats = $this->repository->getCompanyStatistics($lastMonth);

        $totalCalculate = $this->calculatePercentageChange($currentStats->total, $previousStats->total);
        $activeCalculate = $this->calculatePercentageChange($currentStats->active, $previousStats->active);
        $completeDataCalculate = $this->calculatePercentageChange($currentStats->complete_data, $previousStats->complete_data);
        $dataActivateCalculate = $this->calculatePercentageChange($currentStats->data_activate, $previousStats->data_activate);

        return new CompanyWidgetPresenter(
            $currentStats->total,
            $currentStats->active,
            $currentStats->complete_data,
            $currentStats->data_activate,
            $totalCalculate,
            $activeCalculate,
            $completeDataCalculate,
            $dataActivateCalculate
        );
    }

    public function clearWidgetCache(): void
    {
        Cache::forget('company_widget_statistics-'.app()->getLocale());
    }
}
