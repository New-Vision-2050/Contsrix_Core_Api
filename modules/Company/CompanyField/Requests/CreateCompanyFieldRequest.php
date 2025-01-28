<?php

declare(strict_types=1);

namespace Modules\Company\CompanyField\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyField\DTO\CreateCompanyFieldDTO;

class CreateCompanyFieldRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateCompanyFieldDTO(): CreateCompanyFieldDTO
    {
        return new CreateCompanyFieldDTO(
            name: $this->get('name'),
            description : $this->get('description'),
        );
    }
}
