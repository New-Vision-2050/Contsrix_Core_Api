<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240',
            'folder_id' => 'required|uuid|exists:folders,id',
            'visibility' => 'required|string|in:public,private',
        ];
    }

    public function getFile()
    {
        return $this->file('file');
    }

    public function getFolderId(): ?string
    {
        return $this->input('folder_id');
    }

    public function getVisibility(): string
    {
        return $this->input('visibility', 'public');
    }
}
