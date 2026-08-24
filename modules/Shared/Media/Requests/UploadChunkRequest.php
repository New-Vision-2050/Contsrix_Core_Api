<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadChunkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chunk_index' => 'required|integer|min:0',
            'chunk' => 'required|file',
        ];
    }
}
