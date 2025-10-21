<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeFolderStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'status' => 'required|integer|in:0,1'
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.integer' => 'Status must be an integer',
            'status.in' => 'Status must be either 0 or 1'
        ];
    }

    public function getStatus(): int
    {
        return (int) $this->get('status');
    }
}
