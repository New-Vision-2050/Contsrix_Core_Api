<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\ArchiveLibrary\File\DTO\CreateFileDTO;

class CreateFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string',
        ];
    }

    public function createCreateFileDTO(): CreateFileDTO
    {
        return new CreateFileDTO(
            name: $this->get('name'),
        );
    }
}
