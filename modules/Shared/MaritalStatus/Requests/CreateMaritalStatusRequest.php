<?php

declare(strict_types=1);

namespace Modules\Shared\MaritalStatus\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\MaritalStatus\DTO\CreateMaritalStatusDTO;

class CreateMaritalStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateMaritalStatusDTO(): CreateMaritalStatusDTO
    {
        return new CreateMaritalStatusDTO(
            name: $this->get('name'),
        );
    }
}
