<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyRegistrationType\DTO\CreateCompanyRegistrationTypeDTO;

class CreateCompanyRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'type' => 'required|numeric'
        ];
    }

    public function createCreateCompanyRegistrationTypeDTO(): CreateCompanyRegistrationTypeDTO
    {
        return new CreateCompanyRegistrationTypeDTO(
            name: $this->get('name'),
            type: $this->get('type')
        );
    }
}
