<?php

declare(strict_types=1);

namespace Modules\Shared\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitiateChunkedUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
            'total_chunks' => 'required|integer|min:1',
            'mime_type' => 'nullable|string|max:255',
        ];
    }
}
