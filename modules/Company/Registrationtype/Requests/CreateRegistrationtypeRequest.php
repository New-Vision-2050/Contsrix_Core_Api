<?php

declare(strict_types=1);

namespace Modules\Company\RegistrationType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Company\RegistrationType\DTO\CreateRegistrationTypeDTO;

class CreateRegistrationTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateRegistrationTypeDTO(): CreateRegistrationTypeDTO
    {
        return new CreateRegistrationTypeDTO(
            name: $this->get('name'),
        );
    }
}
