<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicQualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicQualification\DTO\CreateAcademicQualificationDTO;

class CreateAcademicQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateAcademicQualificationDTO(): CreateAcademicQualificationDTO
    {
        return new CreateAcademicQualificationDTO(
            name: $this->get('name'),
        );
    }
}
