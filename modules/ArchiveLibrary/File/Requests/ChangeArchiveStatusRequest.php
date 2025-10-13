<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangeArchiveStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:file,folder',
            'status' => 'required|integer|in:0,1'
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Type is required',
            'type.in' => 'Type must be either file or folder',
            'status.required' => 'Status is required',
            'status.integer' => 'Status must be an integer',
            'status.in' => 'Status must be either 0 or 1'
        ];
    }

    public function getType(): string
    {
        return $this->get('type');
    }

    public function getStatus(): int
    {
        return (int) $this->get('status');
    }
}
