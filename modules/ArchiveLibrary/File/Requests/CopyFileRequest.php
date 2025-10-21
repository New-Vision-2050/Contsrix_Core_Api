<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CopyFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_ids' => 'required|array|min:1',
            'file_ids.*' => 'required|uuid|exists:files,id',
            'folder_id' => 'nullable|uuid|exists:folders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file_ids.required' => 'File IDs are required',
            'file_ids.array' => 'File IDs must be an array',
            'file_ids.min' => 'At least one file ID is required',
            'file_ids.*.required' => 'Each file ID is required',
            'file_ids.*.uuid' => 'Each file ID must be a valid UUID',
            'file_ids.*.exists' => 'One or more files do not exist',
            'folder_id.uuid' => 'Folder ID must be a valid UUID',
            'folder_id.exists' => 'Folder does not exist',
        ];
    }

    public function getFileIds(): array
    {
        return $this->input('file_ids', []);
    }

    public function getFolderId(): ?UuidInterface
    {
        return $this->input('folder_id') ? Uuid::fromString($this->input('folder_id')) : null;
    }
}
