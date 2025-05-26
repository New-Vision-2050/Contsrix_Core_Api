<?php

declare(strict_types=1);

namespace Modules\UserInfo\Contactinfo\Presenters;

use Modules\UserInfo\Contactinfo\Models\Contactinfo;
use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\CompanyUser\Models\CompanyUser;

class ContactinfoPresenter extends AbstractPresenter
{
    private ContactInfo $contactInfo;
    public function __construct(ContactInfo $contactInfo)
    {
        $this->contactInfo = $contactInfo;
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->contactInfo->id,
            "email"=> $this->contactInfo->email,
            "other_phone"=> $this->contactInfo->other_phone,
            "code_other_phone"=> $this->contactInfo->code_other_phone,
            "phone"=> $this->contactInfo->phone,
            "phone_code"=> $this->contactInfo->phone_code,
            "landline_number"=> $this->contactInfo->landline_number,
            "address" => $this->contactInfo->address,
            "postal_code" => $this->contactInfo->postal_code
        ];
    }
}
