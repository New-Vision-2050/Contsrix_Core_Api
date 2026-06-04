<?php

declare(strict_types=1);

namespace Modules\Shared\AcademicSpecialization\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\Shared\AcademicSpecialization\Commands\UpdateAcademicSpecializationCommand;
use Modules\Shared\AcademicSpecialization\Handlers\UpdateAcademicSpecializationHandler;

class UpdateAcademicSpecializationRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name_ar' => 'sometimes|required|string|max:255',
            'name_en' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:255|unique:academic_specializations,code,' . $id,
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

    public function createUpdateAcademicSpecializationCommand(): UpdateAcademicSpecializationCommand
    {
        $name = null;
        if ($this->has('name_ar') && $this->has('name_en')) {
            $name = ['ar' => $this->get('name_ar'), 'en' => $this->get('name_en')];
        }

        return new UpdateAcademicSpecializationCommand(
            id: Uuid::fromString($this->route('id')),
            name: $name,
            code: $this->get('code'),
            academicQualificationId: $this->get('academic_qualification_id'),
        );
    }
}
