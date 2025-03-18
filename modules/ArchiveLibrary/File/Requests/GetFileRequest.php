<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }
}
