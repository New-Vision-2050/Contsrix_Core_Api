<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
use App\Rules\Company\CompanyCore\Rules\RegistrationNoRule;
use Modules\Company\CompanyCore\Models\Company;

class UpdateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        if ($this->isDraftClientCompany()) {
            return [
                'name' => ['required', 'regex:/^[\p{Arabic}\s]+$/u'],
                'user_name' => [
                    'required',
                    Rule::unique('companies', 'user_name')->ignore($this->route('id')),
                    'regex:/^[a-zA-Z0-9_]+$/'
                ],
                'country_id' => ['required', 'exists:countries,id'],
                'company_field_id' => ['required', 'array'],
                'company_field_id.*' => ['required', 'uuid', 'exists:company_fields,id'],
                'general_manager_id' => ['required', 'uuid', 'exists:users,id'],
                'is_client' => ['sometimes', 'nullable', 'in:0,1'],
            ];
        }

        return [
            'name' => 'required|regex:/^[\p{Arabic}\s]+$/u',
            'user_name' => [
                'required',
                'unique:companies,user_name,' . $this->route('id'),
                'regex:/^[a-zA-Z0-9_]+$/'
            ],
            'email' => 'required|email',
            'serial_no' => 'required|unique:companies,serial_no,' . $this->route('id'),
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
                new RegistrationNoRule($this->input('registration_type'), $this->input('registration_type_id')),
            ],
        ];
    }

    public function isDraftClientCompany(): bool
    {
        return Company::query()
            ->where('id', $this->route('id'))
            ->where('is_client', 1)
            ->where('is_draft', 1)
            ->exists();
    }

    public function stepOneData(): array
    {
        return $this->safe()->only([
            'name',
            'user_name',
            'country_id',
            'company_field_id',
            'general_manager_id',
            'is_client',
        ]);
    }

    public function createUpdateCompanyCommand(): UpdateCompanyCommand
    {
        return new UpdateCompanyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            userName: $this->get('user_name'),
            email: $this->get('email'),
            phone: $this->get('phone'),
            countryId: $this->get('country_id'),
            companyTypeId: $this->get('company_type_id'),
            companyFieldId: $this->get('company_field_id'),
            registrationTypeId: $this->get('registration_type_id'),
            registrationNo: $this->get('registration_no') ?? '',
            serialNo: $this->get('serial_no'),
            generalManagerId: $this->get('general_manager_id'),
        );
    }
}
