<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;
use Modules\Project\ProjectManagement\DTO\RequestProjectNotificationWorkResumptionDTO;

class RequestProjectNotificationWorkResumptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reasons_resolved' => ['required', 'boolean'],
            'safety_notes_reviewed' => ['required', 'boolean'],
            'site_ready' => ['required', 'boolean'],
            'contractor_notified' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => ['required_with:files', File::types(['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'])->max(20480)],
            'internal_procedure_setting_id' => ['nullable', 'string'],
        ];
    }

    public function toDTO(): RequestProjectNotificationWorkResumptionDTO
    {
        return new RequestProjectNotificationWorkResumptionDTO(
            reasonsResolved: (bool) $this->input('reasons_resolved', false),
            safetyNotesReviewed: (bool) $this->input('safety_notes_reviewed', false),
            siteReady: (bool) $this->input('site_ready', false),
            contractorNotified: (bool) $this->input('contractor_notified', false),
            notes: $this->input('notes'),
            files: $this->hasFile('files') ? $this->file('files') : null,
            internalProcedureSettingId: $this->input('internal_procedure_setting_id'),
        );
    }
}
