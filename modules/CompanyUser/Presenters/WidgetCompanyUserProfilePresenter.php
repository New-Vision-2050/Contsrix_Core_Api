<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Carbon\Carbon;
class WidgetCompanyUserProfilePresenter extends AbstractPresenter
{
    private  $employmentContract;
    private $userSalary;

    public function __construct( $employmentContract, $userSalary)
    {
        $this->employmentContract = $employmentContract;
        $this->userSalary = $userSalary;
    }

    protected function present(bool $isListing = false): array
    {
        $startDate = Carbon::parse($this->employmentContract['start_date']);
        $contractDuration = (int) $this->employmentContract['contract_duration'];

        $endDate = $startDate->copy()->addYears($contractDuration);

        return [
            'contract' => [
                'start_date'   => $startDate->toDateString(),
                'end_date'     => $endDate->toDateString(),
                'user_salary'  => $this->userSalary->salary
            ]
        ];
    }
}
