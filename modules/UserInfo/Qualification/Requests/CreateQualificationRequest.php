<?php

declare(strict_types=1);

namespace Modules\UserInfo\Qualification\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\Qualification\DTO\CreateQualificationDTO;

class CreateQualificationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|string',
            'country_id' => 'required|string',
            'university_id' => 'required|string',
            'academic_qualification_id' => 'required|string',
            'academic_specialization_id' => 'required|string',
            'study_rate' => 'required|numeric',
            'graduation_date' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => __('validation.user_id_required'),
            'country_id.required' => __('validation.country_id_required'),
            'university_id.required' => __('validation.university_id_required'),
            'academic_qualification_id.required' => __('validation.academic_qualification_id_required'),
            'academic_specialization_id.required' => __('validation.academic_specialization_id_required'),
            'study_rate.required' => __('validation.study_rate_required'),
            'study_rate.numeric' => __('validation.study_rate_numeric'),

            'graduation_date.required' => __('validation.graduation_date_required'),
        ];
    }
    public function createCreateQualificationDTO(): CreateQualificationDTO
    {
        return new CreateQualificationDTO(
            company_id: '',//get from controller
            global_id: '',
            country_id:$this->get('country_id'),
            university_id:$this->get('university_id'),
            academic_qualification_id:$this->get('academic_qualification_id'),
            academic_specialization_id:$this->get('academic_specialization_id'),
            study_rate:(int) $this->get('study_rate'),
            graduation_date:$this->get('graduation_date'),
        );
    }
}
