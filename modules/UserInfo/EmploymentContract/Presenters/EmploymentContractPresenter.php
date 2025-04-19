<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Presenters;

use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use BasePackage\Shared\Presenters\AbstractPresenter;

class EmploymentContractPresenter extends AbstractPresenter
{
    private EmploymentContract $employmentContract;

    public function __construct(EmploymentContract $employmentContract)
    {
        $this->employmentContract = $employmentContract;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->employmentContract->id,
            'company_id' => $this->employmentContract->company_id,
            'global_id' => $this->employmentContract->global_id,
            'contract_number' => $this->employmentContract->contract_number,
            'start_date' => $this->employmentContract->start_date,
            'commencement_date' => $this->employmentContract->commencement_date,
            'contract_duration' => $this->employmentContract->contract_duration,
            'notice_period' => $this->employmentContract->notice_period,
            'probation_period' => $this->employmentContract->probation_period,
            'nature_work' => $this->employmentContract->nature_work,
            'type_working_hours' => $this->employmentContract->type_working_hours,
            'working_hours' => $this->employmentContract->working_hours,
            'annual_leave' => $this->employmentContract->annual_leave,
            'country_id' => $this->employmentContract->country_id,
            'country_name' => $this->employmentContract->country->name,
            'right_terminate' => $this->employmentContract->right_terminate,
            'file_url' => $this->employmentContract->getFirstMedia('upload_employment_contracts')?->getFullUrl(),
        ];
    }
}
