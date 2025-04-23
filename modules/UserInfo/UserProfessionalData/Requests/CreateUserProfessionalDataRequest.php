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
            'user_id' => 'required|string',
            'branch_id' => 'required|string',
            'management_id' => 'required|string',
            'department_id' => 'required|string',
            'job_type_id' => 'required|string',
            'job_title_id' => 'required|string',
            'job_code' => 'required|string',
        ];
    }

    public function createCreateUserProfessionalDataDTO(): CreateUserProfessionalDataDTO
    {
        return new CreateUserProfessionalDataDTO(
            company_id: $this->get('company_id'),
            global_id: $this->get('global_id'),
            branch_id: $this->get('branch_id'),
            management_id: $this->get('management_id'),
            department_id: $this->get('department_id'),
            job_type_id: $this->get('job_type_id'),
            job_title_id: $this->get('job_title_id'),
            job_code: $this->get('job_code'),
        );
    }
}
