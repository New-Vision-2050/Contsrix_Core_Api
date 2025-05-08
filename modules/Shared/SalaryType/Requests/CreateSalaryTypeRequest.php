<?php

declare(strict_types=1);

namespace Modules\Shared\SalaryType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\SalaryType\DTO\CreateSalaryTypeDTO;

class CreateSalaryTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateSalaryTypeDTO(): CreateSalaryTypeDTO
    {
        return new CreateSalaryTypeDTO(
            name: $this->get('name'),
        );
    }
}
