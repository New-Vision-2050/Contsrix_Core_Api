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
            'file_id' => 'required|string|uuid|exists:files,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|string|uuid|exists:users,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'File ID is required',
            'file_id.uuid' => 'File ID must be a valid UUID',
            'file_id.exists' => 'The selected file does not exist',
            'user_ids.required' => 'At least one user is required',
            'user_ids.array' => 'User IDs must be an array',
            'user_ids.min' => 'At least one user must be selected',
            'user_ids.*.uuid' => 'Each user ID must be a valid UUID',
            'user_ids.*.exists' => 'One or more selected users do not exist',
        ];
    }

    public function getFileId(): string
    {
        return $this->get('file_id');
    }

    public function getUserIds(): array
    {
        return $this->get('user_ids', []);
    }
}
