<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Models\User;
use Modules\User\Presenters\UserPresenter;

class CompanyUserDataInfoPresenter extends AbstractPresenter
{
    private User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            "id"=>$this->user->id,
            'name' => $this->user->companyUser->name,
            'nickname' => $this->user->companyUser->nickname,
            'gender' => $this->user->companyUser->gender,
            'is_default'=> $this->user->companyUser->is_default,
            'birthdate_gregorian'=> $this->user->companyUser->birthdate_gregorian,
            "birthdate_hijri"=> $this->user->companyUser->birthdate_hijri,
            "country_id"=> $this->user->companyUser->country_id,
            "country" =>$this->user->companyUser?->country?->name,
            "user_types" => $this->user->companyUserCompanies->where("company_id",$this->user->company_id)->map(function ($companyUserCompany) {
                return [
                    'id' => $companyUserCompany->id,
                    'company_id' => $companyUserCompany->company_id,
                    'global_company_user_id' => $companyUserCompany->global_company_user_id,
                    'role' => $companyUserCompany->getRawOriginal('role'),
                    'status' => $companyUserCompany->status,
                ];
            }),


        ];
    }
}
