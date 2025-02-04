<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

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
            'email' => 'required|email',
            'serial_no' => 'required|unique:companies,serial_no',
            'phone' => 'required',
            'country_id' => 'required|exists:countries,id',
            'company_type_id' => 'required|exists:company_types,id',
            'company_field_id' => 'required|exists:company_fields,id',
            'registration_type_id' => 'required|exists:company_registration_types,id',
            'general_manager_id' => 'required',
            'registration_type' => 'required|integer',
            'registration_no' => [
                'nullable',
                'required_if:registration_type,1,2',
                function ($attribute, $value, $fail) {
                    if ($this->input('registration_type') == 1) {
                        if (!preg_match('/^(1|700|40)\d+$/', $value)) {
                            $fail('The ' . $attribute . ' must follow the required pattern.');
                        }
                    } elseif ($this->input('registration_type') == 2) {
                        // This will check if the registration_no is unique based on registration_type_id.
                        $exists = \DB::table('companies')
                            ->where('registration_no', $value)
                            ->where('registration_type_id', $this->input('registration_type_id'))
                            ->exists();

                        if ($exists) {
                            $fail('The ' . $attribute . ' has already been taken for this registration type.');
                        }
                    }
                },
            ],
        ];
    }

    public function createCreateCompanyDTO(): CreateCompanyDTO
    {
        return new CreateCompanyDTO(
            name: $this->get('name'),
            user_name: $this->get('user_name'),
            email: $this->get('email'),
            serial_no: $this->get('serial_no'),
            phone: $this->get('phone'),
            country_id:  $this->get('country_id'),
            company_type_id:  $this->get('company_type_id'),
            company_field_id:  $this->get('company_field_id'),
            general_manager_id:  $this->get('general_manager_id'),
            registration_type_id:  $this->get('registration_type_id'),
            registration_no:  $this->get('registration_no') ?? '',
        );
    }
}
