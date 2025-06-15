<?php

declare(strict_types=1);

namespace Modules\SubEntity\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetSubEntityListByProgramNameRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'program_id' => ['required', 'string', 'exists:programs,id'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
