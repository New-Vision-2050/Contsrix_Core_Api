<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAttachmentRequestRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'serial_number' => 'nullable|string|unique:attachment_requests,serial_number|max:255',
            'name' => 'required|string|max:255',
            'date' => 'required|date',
            'project_id' => 'required|string|exists:projects,id',
            'procedure_setting_id' => 'required|string|uuid|exists:procedure_settings,id',
            // Small files: send directly. Large files: upload via the resumable
            // upload API and pass the resulting tokens in attachment_upload_ids.
            'attachments' => 'nullable|array',
            'attachments.*' => 'file',
            'attachment_upload_ids' => 'nullable|array',
            'attachment_upload_ids.*' => 'string|uuid',
            'notes' => 'nullable|string',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $hasFiles = !empty($this->input('attachments')) || $this->hasFile('attachments');
            $hasTokens = !empty($this->input('attachment_upload_ids'));

            if (!$hasFiles && !$hasTokens) {
                $validator->errors()->add('attachments', 'At least one attachment (file or upload_id) is required.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Request name is required',
            'date.required' => 'Request date is required',
            'date.date' => 'Request date must be a valid date',
            'project_id.required' => 'Project ID is required',
            'project_id.exists' => 'Project does not exist',
            'procedure_setting_id.required' => 'Procedure setting ID is required',
            'procedure_setting_id.uuid' => 'Procedure setting ID must be a valid UUID',
            'procedure_setting_id.exists' => 'Procedure setting does not exist',
            'attachments.array' => 'Attachments must be an array',
            'attachments.*.file' => 'Each attachment must be a file',
            'attachment_upload_ids.array' => 'attachment_upload_ids must be an array',
            'attachment_upload_ids.*.uuid' => 'Each attachment_upload_id must be a valid UUID',
        ];
    }
}
