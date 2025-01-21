<?php

declare(strict_types=1);

namespace Modules\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Auth\DTO\CreateAuthDTO;

class CreateAuthRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateAuthDTO(): CreateAuthDTO
    {
        return new CreateAuthDTO(
            name: $this->get('name'),
        );
    }
}
