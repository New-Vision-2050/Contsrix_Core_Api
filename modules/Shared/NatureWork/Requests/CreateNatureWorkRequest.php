<?php

declare(strict_types=1);

namespace Modules\Shared\NatureWork\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\NatureWork\DTO\CreateNatureWorkDTO;

class CreateNatureWorkRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateNatureWorkDTO(): CreateNatureWorkDTO
    {
        return new CreateNatureWorkDTO(
            name: $this->get('name'),
        );
    }
}
