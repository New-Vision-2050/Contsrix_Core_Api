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
            'parent_id' => 'nullable'
        ];
    }

    public function createUpdateFolderCommand(): UpdateFolderCommand
    {
        return new UpdateFolderCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            parentId:$this->get('parent_id'),
        );
    }
}
