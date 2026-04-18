<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Presenters;

use Modules\CompanyUser\Models\CompanyUser;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Country\Presenters\CountryCurrencyPresenter;
use Modules\Country\Presenters\CountryPresenter;
use Modules\User\Presenters\UserPresenter;
use Modules\UserInfo\BankAccount\Presenters\BankAccountPresenter;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;
use Modules\UserInfo\UserProfessionalData\Presenters\UserProfessionalDataPresenter;

class CompanyUserPresenter extends AbstractPresenter
{
    private CompanyUser $companyUser;
    private ?string $userId;
    public function __construct(CompanyUser $companyUser, string $userId = null)
    {
        $this->companyUser = $companyUser;
        $this->userId = $userId;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->companyUser->id,
            'global_id' => $this->companyUser->global_id,
            'user_id' => $this->companyUser->users()->where("company_id",tenant("id"))->first()?->id,
            'name' => $this->companyUser->name,
            'email' => $this->companyUser->email,
            "residence" => $this->companyUser->residence,
            "passport" => $this->companyUser->passport,
            "identity" => $this->companyUser->identity,
            "border_number" => $this->companyUser->border_number,
            "phone" => $this->companyUser->users()->first()->phone,
            'job_title_id'=>$this->companyUser?->userProfessionalData?->job_title_id,
            "job_title" => $this->companyUser?->userProfessionalData?->jobTitle?->name,
            "country" => $this->companyUser?->country ? (new CountryPresenter($this->companyUser?->country))->getData() : collect([]),
            'data_status' => 0,
            "company" => ($this->companyUser->companies->unique('id')->first())
                ? (new CompanyUsersPresenter(
                    $this->companyUser->companies->unique('id')->first(),
                    $this->companyUser
                ))->getData()
                : null,
//            "client_companies" => CompanyUsersPresenter::collection($this->companyUser->clientCompanies->unique('id'),$this->companyUser),
            "companies" => CompanyUsersPresenter::collection($this->companyUser->companies->unique('id'),$this->companyUser),
            'Job_role' => '-',
            'date_appointment' => '-',
            'branch'=>$this->companyUser->userProfessionalData?->branch != null ? $this->companyUser->userProfessionalData?->branch?->name :"-" ,
            'other_phone'=> $this->companyUser->other_phone??'-',
            'code_other_phone' => $this->companyUser->code_other_phone,
            'address' => $this->companyUser->address??'-',
            'address_attendance' =>  $this->companyUser->address_attendance??'-',
            'image_url' => $this->companyUser->getFirstMedia('upload_user')?->getFullUrl(),
            'bank_account' => $this->companyUser->bankAccount ? (new BankAccountPresenter($this->companyUser->bankAccount))->getData() : null,
            'user_professional_data' => $this->companyUser->userProfessionalData ? (new UserProfessionalDataPresenter($this->companyUser->userProfessionalData))->getData():null,
            "currency"=> $this->companyUser->currency?(new CountryCurrencyPresenter($this->companyUser->currency))->getData():null,
            "photo"=>$this->companyUser->getFirstMedia('upload_user')?->getFullUrl()

        ];
    }
}
