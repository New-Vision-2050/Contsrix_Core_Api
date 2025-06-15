<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Presenters;

use Modules\UserInfo\UserRelative\Models\UserRelative;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Shared\MaritalStatus\Presenters\MaritalStatusPresenter;

class UserRelativePresenter extends AbstractPresenter
{
    private UserRelative $userRelative;

    public function __construct(UserRelative $userRelative)
    {
        $this->userRelative = $userRelative;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->userRelative->id,
            'name' => $this->userRelative->name,
            'company_id' => $this->userRelative->company_id,
            'global_id' => $this->userRelative->global_id,
            'marital_status' => $this->userRelative->maritalStatus ? (new MaritalStatusPresenter($this->userRelative->maritalStatus))->getData() : null,
            'relationship' => $this->userRelative->relationship,
            'phone' => $this->userRelative->phone,
            'marital_status_id' => $this->userRelative->marital_status_id,
        ];
    }
}
