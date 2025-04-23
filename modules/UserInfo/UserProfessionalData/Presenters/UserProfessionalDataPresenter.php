<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Presenters;

use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use BasePackage\Shared\Presenters\AbstractPresenter;

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
            'branch_id' => $this->userProfessionalData->branch_id,
            'management_id' => $this->userProfessionalData->management_id,
            'department_id' => $this->userProfessionalData->department_id,
            'job_type_id' => $this->userProfessionalData->job_type_id,
            'job_title_id' => $this->userProfessionalData->job_title_id,
            'job_code' => $this->userProfessionalData->job_code,
        ];
    }
}
