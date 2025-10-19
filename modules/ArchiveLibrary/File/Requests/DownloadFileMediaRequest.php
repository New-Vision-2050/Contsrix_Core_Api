<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DownloadFileMediaRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'collection' => 'sometimes|string|in:default,upload',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'collection.in' => __('validation.in', ['attribute' => 'collection']),
        ];
    }

    /**
     * Get the collection name from request
     */
    public function getCollection(): string
    {
        return $this->get('collection', 'upload');
    }
}
