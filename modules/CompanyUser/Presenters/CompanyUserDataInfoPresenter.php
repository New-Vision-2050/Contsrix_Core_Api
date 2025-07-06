<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Presenters\UserPresenter;

class CompanyUserDataInfoPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;

    public function __construct(CompanyUser $companyUser)
    {
        $this->companyUser = $companyUser;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'name' => $this->companyUser->name,
            'nickname' => $this->companyUser->nickname,
            'gender' => $this->companyUser->gender == 'male' ? 'ذكر' : 'انثى',
            'is_default'=> $this->companyUser->is_default,
            'birthdate_gregorian'=> $this->companyUser->birthdate_gregorian,
            "birthdate_hijri"=> $this->companyUser->birthdate_hijri,
            "country_id"=> $this->companyUser->country_id,
            "country" =>$this->companyUser?->country?->name
        ];
    }
}
