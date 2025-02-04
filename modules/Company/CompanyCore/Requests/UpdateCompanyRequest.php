<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyCore\Commands\UpdateCompanyCommand;
class UpdateCompanyRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|email|unique:companies,email,' . $this->route('id'),
            'phone' => 'required|unique:companies,phone,' . $this->route('id'),
        ];
    }

    public function createUpdateCompanyCommand(): UpdateCompanyCommand
    {
        return new UpdateCompanyCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            user_name: $this->get('user_name'),
            email: $this->get('email'),
            serial_no: $this->get('serial_no'),
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
