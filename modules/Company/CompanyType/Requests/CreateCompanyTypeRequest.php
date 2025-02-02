<?php

declare(strict_types=1);

namespace Modules\Company\CompanyType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\CompanyType\DTO\CreateCompanyTypeDTO;

class CreateCompanyTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateCompanyTypeDTO(): CreateCompanyTypeDTO
    {
        return new CreateCompanyTypeDTO(
            name: $this->get('name'),
        );
    }
}
