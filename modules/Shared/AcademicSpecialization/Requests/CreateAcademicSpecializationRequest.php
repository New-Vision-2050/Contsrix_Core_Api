<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicSpecialization\DTO\CreateAcademicSpecializationDTO;

class CreateAcademicSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'academic_qualification_id' => 'nullable|uuid|exists:academic_qualifications,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('Academic Specialization Name')]),
            'academic_qualification_id.exists' => __('validation.exists', ['attribute' => __('Academic Qualification')]),
        ];
    }

    public function createCreateAcademicSpecializationDTO(): CreateAcademicSpecializationDTO
    {
        $code = Str::slug($this->get('name'), '-') . '-' . strtolower(Str::random(6));

        return new CreateAcademicSpecializationDTO(
            name: ['ar' => $this->get('name'), 'en' => $this->get('name')],
            code: $code,
            academicQualificationId: $this->get('academic_qualification_id'),
        );
    }
}
