<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\File\Commands\UpdateFileCommand;
use Modules\ArchiveLibrary\File\Handlers\UpdateFileHandler;

class UpdateFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createUpdateFileCommand(): UpdateFileCommand
    {
        return new UpdateFileCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
        );
    }
}
