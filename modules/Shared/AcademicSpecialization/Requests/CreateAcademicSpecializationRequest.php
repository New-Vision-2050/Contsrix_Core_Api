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
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:academic_specializations,code',
            'academic_qualification_id' => 'nullable|uuid|exists:academic_qualifications,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required' => __('validation.required', ['attribute' => __('Academic Specialization Name (Arabic)')]),
            'name_en.required' => __('validation.required', ['attribute' => __('Academic Specialization Name (English)')]),
            'code.required' => __('validation.required', ['attribute' => __('Code')]),
            'code.unique' => __('validation.unique', ['attribute' => __('Code')]),
            'academic_qualification_id.exists' => __('validation.exists', ['attribute' => __('Academic Qualification')]),
        ];
    }

    public function createCreateAcademicSpecializationDTO(): CreateAcademicSpecializationDTO
    {
        return new CreateAcademicSpecializationDTO(
            name: ['ar' => $this->get('name_ar'), 'en' => $this->get('name_en')],
            code: $this->get('code'),
            academicQualificationId: $this->get('academic_qualification_id'),
        );
    }
}
