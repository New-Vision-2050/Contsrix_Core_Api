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
            'new_file' => 'required_without:upload_id|file',
            'upload_id' => 'required_without:new_file|string|uuid',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Item ID is required',
            'item_id.uuid' => 'Item ID must be a valid UUID',
            'item_id.exists' => 'Attachment request item not found',
            'new_file.required_without' => 'New file or upload_id is required',
            'new_file.file' => 'The uploaded file must be a valid file',
            'upload_id.required_without' => 'New file or upload_id is required',
            'upload_id.uuid' => 'upload_id must be a valid UUID',
        ];
    }
}
