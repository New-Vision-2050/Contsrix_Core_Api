<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Presenters;

use Modules\UserInfo\Contactinfo\Models\Contactinfo;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class ContactinfoPresenter extends AbstractPresenter
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
            "email"=> $this->companyUser->email,
            "other_phone"=> $this->companyUser->other_phone,
            "code_other_phone"=> $this->companyUser->code_other_phone,
            "phone"=> $this->companyUser->users->where('id',$this->userId)->first()?->phone,
            "phone_code"=> $this->companyUser->users->where('id',$this->userId)->first()?->phone_code,
            "landline_number"=> $this->companyUser->landline_number,
            "address" => $this->companyUser->address,
            "postal_code" => $this->companyUser->postal_code
        ];
    }
}
