<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserProfessionalData\DTO\CreateUserProfessionalDataDTO;

class CreateUserProfessionalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'branch_id' => 'required|exists:management_hierarchies,id,type,branch',
            'management_id' => 'required|exists:management_hierarchies,id,type,management',
            'job_type_id' => 'required|exists:job_types,id',
            'job_title_id' => 'required|exists:job_titles,id',
            'job_code' => 'required|string',
            'attendance_constraint_id'=> 'nullable|exists:attendance_constraints,id',
        ];
    }
    public function messages(): array
    {
        return [
            'branch_id.required' => __('validation.branch_id_required'),
            'management_id.required' => __('validation.management_id_required'),
            'job_type_id.required' => __('validation.job_type_id_required'),
            'job_title_id.required' => __('validation.job_title_id_required'),
            'job_code.required' => __('validation.job_code_required'),
        ];
    }

    public function createCreateUserProfessionalDataDTO(): CreateUserProfessionalDataDTO
    {
        return new CreateUserProfessionalDataDTO(
            company_id: '',
            global_id: '',
            branch_id: $this->get('branch_id'),
            management_id: $this->get('management_id'),
            job_type_id: $this->get('job_type_id'),
            job_title_id: $this->get('job_title_id'),
            job_code: $this->get('job_code'),
            attendance_constraint_id: $this->get('attendance_constraint_id')
        );
    }
}
