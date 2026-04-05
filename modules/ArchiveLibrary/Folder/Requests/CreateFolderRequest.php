<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\Folder\DTO\CreateFolderDTO;

class CreateFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'parent_id'=>'nullable',
            'project_id'=>'nullable|uuid|exists:projects,id',
            'password'=>'nullable',
            "access_type"=>"required|in:public,private",
            "user_ids"=>"required_if:access_type,private|array",
            "user_ids.*"=>"sometimes|exists:users,id",
            "file"=>"nullable|mimes:jpeg,jpg,png",
            "status"=>"sometimes|integer|in:0,1"
        ];
    }

    public function createCreateFolderDTO(): CreateFolderDTO
    {
        return new CreateFolderDTO(
            name: $this->get('name'),
            parentId: $this->get('parent_id'),
            projectId: $this->get('project_id'),
            password: $this->get('password'),
            accessType: $this->get('access_type'),
            userIds: $this->get('user_ids',[]),
            file: $this->file('file'),
            status: (int) $this->get('status', 1)
        );
    }
}
