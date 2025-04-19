<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserStatus\Presenters;

use Modules\UserInfo\UserStatus\Models\UserStatus;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class UserStatusPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyUser->id,
            'active_type' => $this->companyUser->active_type,
            'active_date_to' => $this->companyUser->active_date_to,
        ];
    }
}
