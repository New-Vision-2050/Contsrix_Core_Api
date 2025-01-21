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
        ];
    }

    public function createCreateCompanyDTO(): CreateCompanyDTO
    {
        return new CreateCompanyDTO(
            name: $this->get('name'),
            email: $this->get('email'),
            phone: $this->get('phone'),
        );
    }
}
