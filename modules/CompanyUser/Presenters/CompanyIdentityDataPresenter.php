<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Presenters\UserPresenter;

class CompanyIdentityDataPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'passport' => $this->companyUser->passport,
            'identity' => $this->companyUser->identity,
            'border_number' => $this->companyUser->border_number,
            'entry_number' => $this->companyUser->entry_number,

            'file_passport' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'file_identity' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'file_border_number' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'file_entry_number' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
        ];
    }
}
