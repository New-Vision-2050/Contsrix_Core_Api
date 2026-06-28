<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationWorkStoppageReportDTO;

class RequestProjectNotificationWorkStoppageReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'other_notes' => ['nullable', 'string'],
            'reasons' => ['required', 'array', 'min:1'],
            'reasons.*.reason_id' => [
                'nullable',
                'uuid',
                Rule::exists('project_notification_work_stoppage_reasons', 'id')->where('is_active', true),
            ],
            'reasons.*.notes' => ['nullable', 'string'],
            'reasons.*.sort_order' => ['nullable', 'integer'],
            'internal_procedure_setting_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function toDTO(): RequestProjectNotificationWorkStoppageReportDTO
    {
        return new RequestProjectNotificationWorkStoppageReportDTO(
            otherNotes: $this->input('other_notes'),
            reasons: $this->input('reasons', []),
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
            files: $this->hasFile('files') ? $this->file('files') : null,
        );
    }
}
