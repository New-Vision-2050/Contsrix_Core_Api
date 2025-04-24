<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\Folder\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class DeleteFolderRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
