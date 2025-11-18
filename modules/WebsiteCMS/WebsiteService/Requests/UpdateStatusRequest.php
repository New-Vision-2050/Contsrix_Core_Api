<?php

namespace Modules\WebsiteCMS\WebsiteService\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:0,1,-1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.integer' => 'Status must be an integer',
            'status.in' => 'Status must be 0 (inactive), 1 (active), or -1 (suspended)',
        ];
    }
}
