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
        if (!$this->employmentContract || !$this->userSalary) {
            return [
                'contract' => [
                    'start_date'   => null,
                    'end_date'     => null,
                    'user_salary'  => null,
                ],
                'message' => 'Some user data is missing.'
            ];
        }

        $startDate = Carbon::parse($this->employmentContract['start_date']);
        $period = (int) $this->employmentContract['notice_period'];
        $unit = $this->employmentContract['contractDurationUnit']['code'];

        switch ($unit) {
            case 'day':
                $endDate = $startDate->copy()->addDays($period);
                break;
            case 'month':
                $endDate = $startDate->copy()->addMonths($period);
                break;
            case 'year':
            default:
                $endDate = $startDate->copy()->addYears($period);
                break;
        }

        return [
            'contract' => [
                'start_date'   => $startDate->toDateString(),
                'end_date'     => $endDate->toDateString(),
                'user_salary'  => $this->userSalary->salary,
            ]
        ];
    }

}
