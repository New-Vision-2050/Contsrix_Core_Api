<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserSalary\Presenters;

use Modules\UserInfo\UserSalary\Models\UserSalary;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Period\Presenters\PeriodPresenter;
use Modules\Shared\SalaryType\Presenters\SalaryTypePresenter;

class UserSalaryPresenter extends AbstractPresenter
{
    private UserSalary $userSalary;

    public function __construct(UserSalary $userSalary)
    {
        $this->userSalary = $userSalary;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userSalary->id,
            'company_id' => $this->userSalary->company_id,
            'global_id' => $this->userSalary->global_id,

            'hour_rate' => $this->userSalary->hour_rate,
            'salary' => $this->userSalary->salary,
            'period_id' => $this->userSalary->period_id,
            'description' => $this->userSalary->description,
            'salary_type_code' => $this->userSalary->salary_type_code,
            'salary_type'=> $this->userSalary->salaryType ? (new SalaryTypePresenter($this->userSalary->salaryType))->getData(): null,
            'period'=> $this->userSalary->period ? (new PeriodPresenter($this->userSalary->period))->getData(): null,
        ];

    }
}
