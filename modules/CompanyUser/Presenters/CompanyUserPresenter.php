<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Presenters\UserPresenter;

class CompanyUserPresenter extends AbstractPresenter
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
            'global_id' => $this->companyUser->global_id,
            'name' => $this->companyUser->name,
            'email' => $this->companyUser->email,
            "residence" => $this->companyUser->residence,
            "passport" => $this->companyUser->passport,
            "identity" => $this->companyUser->identity,
            "border_number" => $this->companyUser->border_number,
            "phone" => $this->companyUser->phone,
            'job_title_id'=>$this->companyUser->job_title_id,
            "job_title" => $this->companyUser?->jobTitle?->name,
            "country" => $this->companyUser?->country ? (new CountryPresenter($this->companyUser?->country))->getData() : collect([]),
            'data_status' => 0,
            "company" => (new CompanyWithRolesPresenter(
                $this->companyUser->companies->unique('id')->first(),
                $this->companyUser
            ))->getData(),
            'Job_role' => '-',
            'date_appointment' => '-',
            'branch'=>'-',
            'other_phone'=> $this->companyUser->other_phone??'-',
            'address' => $this->companyUser->address??'-',
            'address_attendance' =>  $this->companyUser->address_attendance??'-',
            'image_url' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),

//            "users"=> UserPresenter::collection($this->companyUser->users)

        ];
    }
}
