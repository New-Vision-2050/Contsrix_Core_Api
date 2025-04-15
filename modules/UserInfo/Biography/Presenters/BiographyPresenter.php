<?php

declare(strict_types=1);

namespace Modules\UserInfo\Biography\Presenters;

use Modules\UserInfo\Biography\Models\Biography;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class BiographyPresenter extends AbstractPresenter
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
            'files' => $this->companyUser->getFirstMedia('upload_biography')?->getFullUrl(),
        ];
    }
}
