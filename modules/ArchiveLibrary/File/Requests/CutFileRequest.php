<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class CutFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_id' => 'required|uuid|exists:files,id',
            'folder_id' => 'nullable|uuid|exists:folders,id',
        ];
    }

    public function messages(): array
    {
        return [
            'file_id.required' => 'File ID is required',
            'file_id.uuid' => 'File ID must be a valid UUID',
            'file_id.exists' => 'File does not exist',
            'folder_id.uuid' => 'Folder ID must be a valid UUID',
            'folder_id.exists' => 'Folder does not exist',
        ];
    }

    public function getFileId(): UuidInterface
    {
        return Uuid::fromString($this->input('file_id'));
    }

    public function getFolderId(): ?UuidInterface
    {
        return $this->input('folder_id') ? Uuid::fromString($this->input('folder_id')) : null;
    }
}
