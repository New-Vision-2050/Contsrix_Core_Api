<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondToAttachmentItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'item_id' => 'required|string|exists:attachment_request_items,id',
            'action' => 'required|string|in:approve,decline,request_update',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required' => 'Item ID is required',
            'item_id.exists' => 'Attachment item does not exist',
            'action.required' => 'Action is required',
            'action.in' => 'Action must be approve, decline, or request_update',
        ];
    }
}
