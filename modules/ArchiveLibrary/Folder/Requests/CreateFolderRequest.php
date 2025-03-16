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
            'parent_id'=>'nullable'
        ];
    }

    public function createCreateFolderDTO(): CreateFolderDTO
    {
        return new CreateFolderDTO(
            name: $this->get('name'),
            parentId: $this->get('parent_id'),
        );
    }
}
