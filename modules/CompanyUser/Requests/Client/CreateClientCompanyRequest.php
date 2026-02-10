<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\Client\CreateClientCompanyDTO;
use Modules\CompanyUser\DTO\Client\CreateClientDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\SetUserAddressDTO;
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\CompanyUser\Rules\CompanyUserValidation;
use Modules\CompanyUser\Rules\PhoneEmailConsistencyRule;
use Modules\CompanyUser\Rules\UserNameValidation;
use Modules\CompanyUser\Rules\ResidenceValidationRule;
use Modules\CompanyUser\Rules\PassportValidationRule;
use Modules\CompanyUser\Rules\IdentityValidationRule;
use Ramsey\Uuid\Uuid;
use Modules\CompanyUser\DTO\CreateCompanyUserDTO;

class CreateClientCompanyRequest extends FormRequest
{


    public function rules(): array
    {

        return [
            'user_id' => ['required',"exists:users,id"],


            "company_id"=>"required|exists:companies,id"

        ];
    }



    public function CreateClientCompanyDTO():CreateClientCompanyDTO
    {
        return new CreateClientCompanyDTO(
            userId: Uuid::fromString($this->get("user_id")),
            companyId: Uuid::fromString($this->get("company_id")),
        );
    }


}
