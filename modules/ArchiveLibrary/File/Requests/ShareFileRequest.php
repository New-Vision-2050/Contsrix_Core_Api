<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class ShareFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'required|string|uuid|exists:files,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file_ids.required' => 'At least one file is required',
            'file_ids.array' => 'File IDs must be an array',
            'file_ids.min' => 'At least one file must be selected',
            'file_ids.*.uuid' => 'Each file ID must be a valid UUID',
            'file_ids.*.exists' => 'One or more selected files do not exist',
            'user_ids.required' => 'At least one user is required',
            'user_ids.array' => 'User IDs must be an array',
            'user_ids.min' => 'At least one user must be selected',
            'user_ids.*.uuid' => 'Each user ID must be a valid UUID',
            'user_ids.*.exists' => 'One or more selected users do not exist',
        ];
    }

    public function getFileIds(): array
    {
        return $this->get('file_ids', []);
    }

    public function getUserIds(): array
    {
        return $this->get('user_ids', []);
    }
}
