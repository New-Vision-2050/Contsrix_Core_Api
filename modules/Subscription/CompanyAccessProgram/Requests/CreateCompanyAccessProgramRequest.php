<?php

declare(strict_types=1);

namespace Modules\Subscription\CompanyAccessProgram\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Subscription\CompanyAccessProgram\DTO\CreateCompanyAccessProgramDTO;

class CreateCompanyAccessProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateCompanyAccessProgramDTO(): CreateCompanyAccessProgramDTO
    {
        return new CreateCompanyAccessProgramDTO(
            name: $this->get('name'),
        );
    }
}
