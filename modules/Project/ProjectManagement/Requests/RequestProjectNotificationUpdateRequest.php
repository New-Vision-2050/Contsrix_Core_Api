<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationUpdateDTO;

class RequestProjectNotificationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notification_type'           => ['nullable', 'string', 'max:255'],
            'feeder_number'               => ['nullable', 'string', 'max:255'],
            'work_description'            => ['nullable', 'string'],
            'contractor_name'             => ['nullable', 'string', 'max:255'],
            'contractor_technical_name'   => ['nullable', 'string', 'max:255'],
            'contractor_mobile'           => ['nullable', 'string', 'max:30'],
            'task_latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'task_longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'notes'                       => ['nullable', 'string', 'max:2000'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files'                       => ['nullable', 'array'],
            'files.*'                     => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function toDTO(): RequestProjectNotificationUpdateDTO
    {
        return new RequestProjectNotificationUpdateDTO(
            notificationType: $this->input('notification_type'),
            feederNumber: $this->input('feeder_number'),
            workDescription: $this->input('work_description'),
            contractorName: $this->input('contractor_name'),
            contractorTechnicalName: $this->input('contractor_technical_name'),
            contractorMobile: $this->input('contractor_mobile'),
            taskLatitude: $this->filled('task_latitude') ? (float) $this->input('task_latitude') : null,
            taskLongitude: $this->filled('task_longitude') ? (float) $this->input('task_longitude') : null,
            notes: $this->input('notes'),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
        );
    }
}
