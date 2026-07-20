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
            'machine_number'              => ['nullable', 'string', 'max:255'],
            'work_description'            => ['nullable', 'string'],
            'contractor_name'             => ['nullable', 'string', 'max:255'],
            'contractor_representative_id' => ['nullable', 'uuid', 'exists:project_contractor_representatives,id'],
            'contractor_mobile'           => ['nullable', 'string', 'max:30'],
            'task_latitude'               => ['nullable', 'numeric', 'between:-90,90'],
            'task_longitude'              => ['nullable', 'numeric', 'between:-180,180'],
            'permit_source'               => ['nullable', 'string', 'max:255'],
            'permit_recipient'            => ['nullable', 'string', 'max:255'],
            'notes'                       => ['nullable', 'string', 'max:2000'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files'                       => ['nullable', 'array'],
            'files.*'                     => ['file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'site_status_type_id'         => ['nullable', 'uuid', 'exists:project_notification_site_status_types,id'],
            'site_status_type_values'     => ['nullable', 'array'],
            'site_status_type_values.*.key_id' => ['required_with:site_status_type_values', 'uuid', 'exists:project_notification_site_status_type_keys,id'],
            'site_status_type_values.*.value' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): RequestProjectNotificationUpdateDTO
    {
        return new RequestProjectNotificationUpdateDTO(
            notificationType: $this->input('notification_type'),
            feederNumber: $this->input('feeder_number'),
            machineNumber: $this->input('machine_number'),
            workDescription: $this->input('work_description'),
            contractorName: $this->input('contractor_name'),
            contractorRepresentativeId: $this->input('contractor_representative_id'),
            contractorMobile: $this->input('contractor_mobile'),
            taskLatitude: $this->filled('task_latitude') ? (float) $this->input('task_latitude') : null,
            taskLongitude: $this->filled('task_longitude') ? (float) $this->input('task_longitude') : null,
            permitSource: $this->input('permit_source'),
            permitRecipient: $this->input('permit_recipient'),
            notes: $this->input('notes'),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            siteStatusTypeId: $this->input('site_status_type_id'),
            siteStatusTypeValues: $this->input('site_status_type_values'),
        );
    }
}
