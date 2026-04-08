<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondToShareRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'share_id' => 'required|string|exists:resource_shares,id',
            'action' => 'required|in:accept,reject',
        ];
    }

    public function messages(): array
    {
        return [
            'share_id.required' => 'Share ID is required',
            'share_id.exists' => 'Share does not exist',
            'action.required' => 'Action is required',
            'action.in' => 'Action must be either accept or reject',
        ];
    }
}
