<?php

declare(strict_types=1);

namespace Modules\CompanyUser\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Modules\CompanyUser\DTO\Broker\CreateBrokerDTO;
use Modules\CompanyUser\DTO\CreateCompanyUserCompanyRoleDTO;
use Modules\CompanyUser\DTO\Employee\CreateEmployeeDTO;
use Modules\CompanyUser\DTO\Employee\UpdateEmployeeDTO;
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

class UpdateEmployeeRequest extends FormRequest
{


    public function rules(): array
    {
        return [

            "branch_id" => "required|exists:management_hierarchies,id,type,branch",
            "status" => 'required|in:1,0',



        ];
    }



    public function createUpdateEmployeeDTO(): UpdateEmployeeDTO
    {
        return new UpdateEmployeeDTO(
            id:$this->route("id"),

            status:(int) $this->get("status"),
            branchId:(int) $this->get('branch_id'),
        );
    }

}
