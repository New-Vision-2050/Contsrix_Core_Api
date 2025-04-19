<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicSpecialization\DTO\CreateAcademicSpecializationDTO;

class CreateAcademicSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateAcademicSpecializationDTO(): CreateAcademicSpecializationDTO
    {
        return new CreateAcademicSpecializationDTO(
            name: $this->get('name'),
        );
    }
}
