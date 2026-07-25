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
            'attachments' => 'required|array|min:1',
            'attachments.*' => 'required|file', // 10MB max
            'notes' => 'nullable|string',
        ];
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
            'attachments.required' => 'At least one attachment is required',
            'attachments.array' => 'Attachments must be an array',
            'attachments.min' => 'At least one attachment is required',
            'attachments.*.required' => 'Attachment file is required',
            'attachments.*.file' => 'Each attachment must be a file',
            'attachments.*.max' => 'Each attachment must not exceed 10MB',
        ];
    }
}
