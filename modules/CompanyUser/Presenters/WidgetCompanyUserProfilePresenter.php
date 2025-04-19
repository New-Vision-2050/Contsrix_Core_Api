<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Carbon\Carbon;

class WidgetCompanyUserProfilePresenter extends AbstractPresenter
{
    private array $getCompanyStatistics;

    public function __construct(array $getCompanyStatistics)
    {
        $this->getCompanyStatistics = $getCompanyStatistics;
    }

    protected function present(bool $isListing = false): array
    {
        $startDate = Carbon::parse($this->getCompanyStatistics['start_date']);
        $contractDuration = (int) $this->getCompanyStatistics['contract_duration'];

        $endDate = $startDate->copy()->addYears($contractDuration);

        return [
            'contract' => [
                'start_date' => $startDate->toDateString(),
                'end_date'   => $endDate->toDateString(),
            ]
        ];
    }
}
