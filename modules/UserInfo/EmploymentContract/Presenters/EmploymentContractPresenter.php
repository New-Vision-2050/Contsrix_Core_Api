<?php

declare(strict_types=1);

namespace Modules\UserInfo\EmploymentContract\Presenters;

use Modules\UserInfo\EmploymentContract\Models\EmploymentContract;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Media\Presenters\MediaPresenter;
use Modules\Shared\NatureWork\Presenters\NatureWorkPresenter;
use Modules\Shared\RightTerminate\Presenters\RightTerminatePresenter;
use Modules\Shared\TimeUnit\Presenters\TimeUnitPresenter;
use Modules\Shared\TypeWorkingHour\Presenters\TypeWorkingHourPresenter;

class EmploymentContractPresenter extends AbstractPresenter
{
    private EmploymentContract $employmentContract;

    public function __construct(EmploymentContract $employmentContract)
    {
        $this->employmentContract = $employmentContract;
    }

    protected function present(bool $isListing = false): array
    {
        $firstMedia = $this->employmentContract->getFirstMedia('upload_employment_contracts');

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
            'working_hours' => $this->employmentContract->working_hours,
            'annual_leave' => $this->employmentContract->annual_leave,
            'country_id' => $this->employmentContract->country_id,
            'country_name' => $this->employmentContract?->country?->name??null,

            'latitude' => $this->employmentContract->latitude,
            'longitude' => $this->employmentContract->longitude,
            'files' => MediaPresenter::collection($this->employmentContract->getMedia('upload_employment_contracts')),

            'type_working_hour'  => $this->employmentContract->typeWorkingHour ?(new TypeWorkingHourPresenter($this->employmentContract->typeWorkingHour))->getData() : null,
            'right_terminate'  => $this->employmentContract->rightTerminate ?(new RightTerminatePresenter($this->employmentContract->rightTerminate))->getData() : null,
            'nature_work'  => $this->employmentContract->natureWork ?(new NatureWorkPresenter($this->employmentContract->natureWork))->getData() : null,

            'contract_duration_unit'=> $this->employmentContract->contractDurationUnit ?(new TimeUnitPresenter($this->employmentContract->contractDurationUnit))->getData() : null,
            'notice_period_unit'=> $this->employmentContract->noticePeriodUnit ?(new TimeUnitPresenter($this->employmentContract->noticePeriodUnit))->getData() : null,
            'probation_period_unit' => $this->employmentContract->probationPeriodUnit ?(new TimeUnitPresenter($this->employmentContract->probationPeriodUnit))->getData() : null,

        ];
    }
}
