<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\Folder\Commands\UpdateFolderCommand;
use Modules\ArchiveLibrary\Folder\Handlers\UpdateFolderHandler;

class UpdateFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id' => 'nullable',
            'project_id'=>'nullable|uuid|exists:projects,id',
            'password' => 'nullable',
            'access_type' => 'required|in:public,private',
            'user_ids' => 'required_if:access_type,private|array',
            'user_ids.*' => 'sometimes|exists:users,id',
            "file"=>"nullable",
            "status" => "sometimes|integer|in:0,1"
        ];
    }

    public function createUpdateFolderCommand(): UpdateFolderCommand
    {
        return new UpdateFolderCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            parentId: $this->get('parent_id'),
            projectId: $this->get('project_id'),
            password: $this->get('password'),
            accessType: $this->get('access_type'),
            userIds: $this->get('user_ids', []),
            file: $this->hasFile('file') ? $this->file("file") : null,
            status: $this->has('status') ? (int) $this->get('status') : null
        );
    }
}
