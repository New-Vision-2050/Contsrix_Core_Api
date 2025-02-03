<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;

class CreateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'user_name' => 'required|unique:companies,user_name',
            'email' => 'required|email',
            'phone' => 'required',
            'country_id' => 'required|exists:countries,id',
            'company_type_id' => 'required|exists:company_types,id',
            'company_field_id' => 'required|exists:company_fields,id',
            'registration_type_id' => 'required|exists:company_registration_types,id',
            'general_manager_id' => 'required|exists:users,id',
            'registration_type'=> 'required',
            'registration_no' => [
                'nullable',
                'required_if:registration_type,1',
                'regex:/^(1|700|40)\d+$/'
            ],
            'classification_no' => 'nullable|unique:company_registration_forms,classification_no|required_if:registration_type,2',
        ];
    }

    public function createCreateCompanyDTO(): CreateCompanyDTO
    {
        return new CreateCompanyDTO(
            name: $this->get('name'),
            user_name: $this->get('user_name'),
            email: $this->get('email'),
            phone: $this->get('phone'),
            country_id:  $this->get('country_id'),
            company_type_id:  $this->get('company_type_id'),
            company_field_id:  $this->get('company_field_id'),
            general_manager_id:  $this->get('general_manager_id'),
            registration_type_id:  $this->get('registration_type_id'),
            registration_no:  $this->get('registration_no') ?? '',
            classification_no: $this->get('classification_no')??'',
        );
    }
}
