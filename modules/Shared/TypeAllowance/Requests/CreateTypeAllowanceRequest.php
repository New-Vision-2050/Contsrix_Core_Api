<?php

declare(strict_types=1);

namespace Modules\Shared\TypeAllowance\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\TypeAllowance\DTO\CreateTypeAllowanceDTO;

class CreateTypeAllowanceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateTypeAllowanceDTO(): CreateTypeAllowanceDTO
    {
        return new CreateTypeAllowanceDTO(
            name: $this->get('name'),
        );
    }
}
