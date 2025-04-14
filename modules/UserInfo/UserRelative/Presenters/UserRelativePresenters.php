<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserRelative\Presenters;

use Modules\UserInfo\UserRelative\Models\UserRelative;
use BasePackage\Shared\Presenters\AbstractPresenter;

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
            'company_id',
            'global_id',
            'marital_status',
            'relationship',
            'phone',
        ];
    }
}
