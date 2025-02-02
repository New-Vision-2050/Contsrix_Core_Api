<?php

declare(strict_types=1);

namespace Modules\Company\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\Commands\UpdateCompanyCommand;
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
            email: $this->get('email'),
            phone: $this->get('phone'),
            country_id:  $this->get('country_id'),
            company_type_id:  $this->get('company_type_id'),
            company_field_id:  $this->get('company_field_id'),
            registration_type_id:  $this->get('registration_type_id'),
            registration_no:  $this->get('registration_no') ?? '',
            classification_no: $this->get('classification_no') ?? '',
            general_manager_id:  $this->get('general_manager_id')
        );
    }
}
