<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use App\Rules\Company\CompanyCore\Rules\RegistrationNoRule;
use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Illuminate\Validation\Rule;

class CreateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'user_name' => [
                'required',
                'unique:companies,user_name',
                'regex:/^[a-zA-Z0-9_]+$/'
            ],
            // 'email' => 'required|email',
            // 'serial_no' => 'required|unique:companies,serial_no',
            // 'phone' => 'required',
            'country_id' => 'required|exists:countries,id',
            // 'company_type_id' => 'required|exists:company_types,id',
            'company_field_id' => 'required|exists:company_fields,id',
            // 'registration_type_id' => 'required|exists:company_registration_types,id',
            'general_manager_id' => 'required',
            // 'registration_type' => 'required|integer',
            // 'registration_no' => [
            //     'nullable',
            //     'required_if:registration_type,1,2',
            //     new RegistrationNoRule($this->input('registration_type'), $this->input('registration_type_id')),
            // ],
        ];
    }

    public function createCreateCompanyDTO(): CreateCompanyDTO
    {
        return new CreateCompanyDTO(
            name: $this->get('name'),
            userName: $this->get('user_name'),
            // email: $this->get('email'),
            // serialNo: $this->get('serial_no'),
            // phone: $this->get('phone'),
            countryId:  $this->get('country_id'),
            // companyTypeId:  $this->get('company_type_id'),
            companyFieldId:  $this->get('company_field_id'),
            generalManagerId:  $this->get('general_manager_id'),
            // registrationTypeId:  $this->get('registration_type_id'),
            // registrationNo:  $this->get('registration_no') ?? '',
        );
    }
}
