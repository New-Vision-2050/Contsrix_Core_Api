<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserPrivilege\Presenters;

use Modules\UserInfo\UserPrivilege\Models\UserPrivilege;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\Period\Presenters\PeriodPresenter;
use Modules\Shared\Privilege\Presenters\PrivilegePresenter;
use Modules\Shared\TypeAllowance\Presenters\TypeAllowancePresenter;
use Modules\Shared\TypePrivilege\Presenters\TypePrivilegePresenter;

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
            'type_privilege_id' => $this->userPrivilege->type_privilege_id,
            'type_allowance_id' => $this->userPrivilege->type_allowance_id,
            'period_id' => $this->userPrivilege->period_id,
            'type_privilege'=> $this->userPrivilege->typePrivilege? (new TypePrivilegePresenter($this->userPrivilege->typePrivilege))->getData() : null,
            'type_allowance'=> $this->userPrivilege->typeAllowance ? (new TypeAllowancePresenter($this->userPrivilege->typeAllowance))->getData(): null,
            'charge_amount'=> $this->userPrivilege->chargeAmount,
            'description'=> $this->userPrivilege->description,
            'period'=> $this->userPrivilege->period ? (new PeriodPresenter($this->userPrivilege->period))->getData() : null,
            'privilege' => $this->userPrivilege->privilege ? (new PrivilegePresenter($this->userPrivilege->privilege))->getData(): null,
        ];
    }
}
