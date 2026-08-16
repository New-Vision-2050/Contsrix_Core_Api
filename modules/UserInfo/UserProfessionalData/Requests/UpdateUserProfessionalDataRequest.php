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
            'job_type_id' => 'required|string',
            'job_title_id' => 'required|string',
            'job_code' => 'required|string',
            'attendance_type' => ['nullable', 'string', 'in:regular,flexible,flexable,flex'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('attendance_type')) {
            $this->merge([
                'attendance_type' => \Modules\Attendance\Support\AttendanceType::normalize($this->input('attendance_type')),
            ]);
        }
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
    public function createUpdateUserProfessionalDataCommand(): UpdateUserProfessionalDataCommand
    {
        return new UpdateUserProfessionalDataCommand(
            id: Uuid::fromString($this->route('id')),
            branch_id: $this->get('branch_id'),
            management_id: $this->get('management_id'),
            job_type_id: $this->get('job_type_id'),
            job_title_id: $this->get('job_title_id'),
            job_code: $this->get('job_code'),
            attendance_type: $this->filled('attendance_type')
                ? \Modules\Attendance\Support\AttendanceType::normalize($this->get('attendance_type'))
                : null,
        );
    }
}
