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
        // 1. Get the directly assigned attendance constraint
        $effectiveConstraint = $this->userProfessionalData->attendanceConstraint;

        // Defensive check: If it's a Collection (unlikely for belongsTo, but possible), get the first item
        if ($effectiveConstraint instanceof Collection) {
            $effectiveConstraint = $effectiveConstraint->first();
        }

        // 2. If no direct constraint, and a branch exists, try to get the default constraint from the branch
        if (is_null($effectiveConstraint) && $this->userProfessionalData->branch) {
            $defaultConstraintFromBranch = $this->userProfessionalData->branch->defaultAttendanceConstraint;

            // Defensive check: If the default constraint from branch is a Collection, get the first item
            if ($defaultConstraintFromBranch instanceof Collection) {
                $effectiveConstraint = $defaultConstraintFromBranch->first();
            } else {
                // Otherwise, it's already a single model or null, assign directly
                $effectiveConstraint = $defaultConstraintFromBranch;
            }
        }

        return [
            'id' => $this->userProfessionalData->id,
            'company_id' => $this->userProfessionalData->company_id,
            'global_id' => $this->userProfessionalData->global_id,

            'branch' => $this->userProfessionalData->branch ? (new ManagementHierarchyPresenter($this->userProfessionalData->branch))->getData() : null,
            'management' => $this->userProfessionalData->management? (new ManagementHierarchyPresenter($this->userProfessionalData->management))->getData() : null,
            'department' => $this->userProfessionalData->department? (new ManagementHierarchyPresenter($this->userProfessionalData->department))->getData() : null,
            'job_type' => $this->userProfessionalData->jobType ? (new JobTypePresenter($this->userProfessionalData->jobType))->getData() : null,
            'job_title' => $this->userProfessionalData->jobTitle ? (new JobTitlePresenter($this->userProfessionalData->jobTitle))->getData() : null,
            'job_code' => $this->userProfessionalData->job_code,

            // Pass the guaranteed single model or null to the presenter
            'attendance_constraint' => $effectiveConstraint ? (new ConstraintListPresenter($effectiveConstraint))->getData() : null,
        ];
    }
}
