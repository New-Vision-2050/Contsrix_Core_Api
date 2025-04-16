<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Presenters;

use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Privilege\Presenters\PrivilegePresenter;

class UserPrivilegePresenter extends AbstractPresenter
{
    private UserPrivilege $userPrivilege;

    public function __construct(UserPrivilege $userPrivilege)
    {
        $this->userPrivilege = $userPrivilege;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userPrivilege->id,
            'type_privilege'=> $this->userPrivilege->type_privilege,
            'type_allowance'=> $this->userPrivilege->type_allowance,
            'rate'=> $this->userPrivilege->rate,
            'description'=> $this->userPrivilege->description,
            'period',
            'insurance_company',
            'insurance_number',
            'privilege' => $this->userPrivilege->privilege
            ? (new PrivilegePresenter($this->userPrivilege->privilege))->getData()
            : null,
        ];
    }
}
