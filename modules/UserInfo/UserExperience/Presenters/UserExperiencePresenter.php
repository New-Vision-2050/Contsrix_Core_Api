<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserExperience\Presenters;

use Modules\UserInfo\UserExperience\Models\UserExperience;
use BasePackage\Shared\Presenters\AbstractPresenter;

class UserExperiencePresenter extends AbstractPresenter
{
    private UserExperience $userExperience;

    public function __construct(UserExperience $userExperience)
    {
        $this->userExperience = $userExperience;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userExperience->id,
            'company_id' => $this->userExperience->company_id,
            'global_id' => $this->userExperience->global_id,
            'job_name' => $this->userExperience->job_name,
            'training_from' => $this->userExperience->training_from,
            'training_to' => $this->userExperience->training_to,
            'company_name' => $this->userExperience->company_name,
            'about' => $this->userExperience->about,
        ];
    }
}
