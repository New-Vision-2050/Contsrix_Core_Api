<?php

declare(strict_types=1);

namespace Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\DTO\CreateCompanyDTO;

class CreateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:companies,email',
            'phone' => 'required|unique:companies,phone',
            'country_id' => 'required|exists:countries,id',
            'company_type_id' => 'required|exists:company_types,id',
            'company_field_id' => 'required|exists:company_fields,id',
            'registration_type_id' => 'required|exists:company_registration_types,id',
            'general_manager_id' => 'required|exists:users,id',
            'registration_no' => 'nullable|unique:company_registration_forms,registration_no',
        ];
    }

    public function createCreateCompanyDTO(): CreateCompanyDTO
    {
        return new CreateCompanyDTO(
            name: $this->get('name'),
            email: $this->get('email'),
            phone: $this->get('phone'),
            country_id:  $this->get('country_id'),
            company_type_id:  $this->get('company_type_id'),
            company_field_id:  $this->get('company_field_id'),
            registration_type_id:  $this->get('registration_type_id'),
            registration_no:  $this->get('registration_no') ?? '',
            general_manager_id:  $this->get('general_manager_id')
        );
    }
}
