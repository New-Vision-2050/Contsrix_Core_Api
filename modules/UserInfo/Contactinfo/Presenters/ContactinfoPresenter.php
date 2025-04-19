<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Presenters;

use Modules\UserInfo\Contactinfo\Models\Contactinfo;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class ContactinfoPresenter extends AbstractPresenter
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
            "email"=> $this->companyUser->email,
            "other_phone"=> $this->companyUser->other_phone,
            "phone"=> $this->companyUser->phone ,
            "phone_code"=> $this->companyUser->phone_code,
            "landline_number"=> $this->companyUser->landline_number,
            "address" => $this->companyUser->address,
            "postal_code" => $this->companyUser->postal_code
        ];
    }
}
