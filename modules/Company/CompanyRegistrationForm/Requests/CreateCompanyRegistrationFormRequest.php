<?php

declare(strict_types=1);

namespace Modules\Company\CompanyRegistrationForm\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyRegistrationForm\DTO\CreateCompanyRegistrationFormDTO;

class CreateCompanyRegistrationFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateCompanyRegistrationFormDTO(): CreateCompanyRegistrationFormDTO
    {
        return new CreateCompanyRegistrationFormDTO(
            name: $this->get('name'),
        );
    }
}
