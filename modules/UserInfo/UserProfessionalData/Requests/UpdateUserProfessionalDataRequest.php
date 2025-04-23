<?php

declare(strict_types=1);

namespace Modules\UserInfo\UserProfessionalData\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\UserInfo\UserProfessionalData\Commands\UpdateUserProfessionalDataCommand;
use Modules\UserInfo\UserProfessionalData\Handlers\UpdateUserProfessionalDataHandler;

class UpdateUserProfessionalDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'branch_id' => 'required|string',
            'management_id' => 'required|string',
            'department_id' => 'required|string',
            'job_type_id' => 'required|string',
            'job_title_id' => 'required|string',
            'job_code' => 'required|string',
        ];
    }

    public function createUpdateUserProfessionalDataCommand(): UpdateUserProfessionalDataCommand
    {
        return new UpdateUserProfessionalDataCommand(
            id: Uuid::fromString($this->route('id')),
            branch_id: $this->get('branch_id'),
            management_id: $this->get('management_id'),
            department_id: $this->get('department_id'),
            job_type_id: $this->get('job_type_id'),
            job_title_id: $this->get('job_title_id'),
            job_code: $this->get('job_code'),
        );
    }
}
