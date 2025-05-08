<?php

declare(strict_types=1);

namespace Modules\Program\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Program\DTO\CreateProgramDTO;

class CreateProgramRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateProgramDTO(): CreateProgramDTO
    {
        return new CreateProgramDTO(
            name: $this->get('name'),
        );
    }
}
