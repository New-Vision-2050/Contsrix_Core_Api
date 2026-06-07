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
        return [
            'name' => 'sometimes|required|string|max:255',
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

    public function createUpdateAcademicSpecializationCommand(): UpdateAcademicSpecializationCommand
    {
        $name = $this->has('name') ? ['ar' => $this->get('name'), 'en' => $this->get('name')] : null;

        return new UpdateAcademicSpecializationCommand(
            id: Uuid::fromString($this->route('id')),
            name: $name,
            academicQualificationId: $this->get('academic_qualification_id'),
        );
    }
}
