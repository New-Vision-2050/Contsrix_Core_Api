<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Presenters;

use Modules\Shared\JobType\Models\JobType;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Presenters\ConstraintListPresenter;
use Modules\Company\ManagementHierarchy\Presenters\ManagementHierarchyPresenter;
use Modules\JobTitle\Presenters\JobTitlePresenter;
use Modules\Shared\JobType\Presenters\JobTypePresenter;
use Illuminate\Database\Eloquent\Collection; // Import Collection to check its instance

class UserProfessionalDataPresenter extends AbstractPresenter
{
    private UserProfessionalData $userProfessionalData;

    public function __construct(UserProfessionalData $userProfessionalData)
    {
        $this->userProfessionalData = $userProfessionalData;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userProfessionalData->id,
            'company_id' => $this->userProfessionalData->company_id,
            'global_id' => $this->userProfessionalData->global_id,
            'user_id'=> $this->userProfessionalData->user_id,
            'branch' => $this->userProfessionalData->branch ? (new ManagementHierarchyPresenter($this->userProfessionalData->branch))->getData() : null,
            'management' => $this->userProfessionalData->management? (new ManagementHierarchyPresenter($this->userProfessionalData->management))->getData() : null,
            'department' => $this->userProfessionalData->department? (new ManagementHierarchyPresenter($this->userProfessionalData->department))->getData() : null,
            'job_type' => $this->userProfessionalData->jobType ? (new JobTypePresenter($this->userProfessionalData->jobType))->getData() : null,
            'job_title' => $this->userProfessionalData->jobTitle ? (new JobTitlePresenter($this->userProfessionalData->jobTitle))->getData() : null,
            'job_code' => $this->userProfessionalData->job_code,

            // Pass the guaranteed single model or null to the presenter
            'attendance_constraint' =>$this->userProfessionalData->attendanceConstraint ? (new ConstraintListPresenter($this->userProfessionalData->attendanceConstraint))->getData() : null,
        ];
    }
}
