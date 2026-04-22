<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReplaceMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id' => 'required|string|uuid|exists:attachment_request_items,id',
            'new_file' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Item ID is required',
            'item_id.uuid' => 'Item ID must be a valid UUID',
            'item_id.exists' => 'Attachment request item not found',
            'new_file.required' => 'New file is required',
            'new_file.file' => 'The uploaded file must be a valid file',
            'new_file.max' => 'File size must not exceed 10MB',
        ];
    }
}
