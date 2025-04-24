<?php

declare(strict_types=1);

namespace Modules\Shared\ProfessionalBodie\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\ProfessionalBodie\DTO\CreateProfessionalBodieDTO;

class CreateProfessionalBodieRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateProfessionalBodieDTO(): CreateProfessionalBodieDTO
    {
        return new CreateProfessionalBodieDTO(
            name: $this->get('name'),
        );
    }
}
