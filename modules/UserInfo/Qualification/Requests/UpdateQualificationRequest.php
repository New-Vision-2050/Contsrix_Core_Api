<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Qualification\Commands\UpdateQualificationCommand;
use Modules\UserInfo\Qualification\Handlers\UpdateQualificationHandler;

class UpdateQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'country_id' => 'required|string',
            'university_id' => 'required|string',
            'academic_qualification_id' => 'required|string',
            'academic_specialization_id' => 'required|string',
            'study_rate' => 'required|string',
            'graduation_date' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'country_id.required' => __('validation.country_id_required'),
            'university_id.required' => __('validation.university_id_required'),
            'academic_qualification_id.required' => __('validation.academic_qualification_id_required'),
            'academic_specialization_id.required' => __('validation.academic_specialization_id_required'),
            'study_rate.required' => __('validation.study_rate_required'),
            'graduation_date.required' => __('validation.graduation_date_required'),
        ];
    }

    public function createUpdateQualificationCommand(): UpdateQualificationCommand
    {
        return new UpdateQualificationCommand(
            id: Uuid::fromString($this->route('id')),
            country_id:$this->get('country_id'),
            university_id:$this->get('university_id'),
            academic_qualification_id:$this->get('academic_qualification_id'),
            academic_specialization_id:$this->get('academic_specialization_id'),
            study_rate:$this->get('study_rate'),
            graduation_date:$this->get('graduation_date'),
        );
    }
}
