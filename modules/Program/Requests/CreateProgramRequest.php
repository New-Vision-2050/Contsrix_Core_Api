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
            'name_en' => 'required|string|unique:programs,name->en',
            'name_ar' => 'required|string|unique:programs,name->ar',
        ];
    }

    public function createCreateProgramDTO(): CreateProgramDTO
    {
        return new CreateProgramDTO(
            name: [
                'en' => $this->get('name_en'),
                'ar' => $this->get('name_ar'),
            ]
        );

    }
}
