<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\SubEntity\DTO\CreateSubEntityDTO;

class CreateSubEntityRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateSubEntityDTO(): CreateSubEntityDTO
    {
        return new CreateSubEntityDTO(
            name: $this->get('name'),
        );
    }
}
