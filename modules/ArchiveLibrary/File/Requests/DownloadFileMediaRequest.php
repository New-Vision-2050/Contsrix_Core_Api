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
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|uuid|exists:files,id',
            'collection' => 'sometimes|string|in:default,upload',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ids.required' => __('validation.required', ['attribute' => 'ids']),
            'ids.array' => __('validation.array', ['attribute' => 'ids']),
            'ids.min' => 'At least one file ID is required',
            'ids.*.uuid' => 'Each file ID must be a valid UUID',
            'ids.*.exists' => 'One or more file IDs do not exist',
            'collection.in' => __('validation.in', ['attribute' => 'collection']),
        ];
    }

    /**
     * Get the file IDs array from request
     */
    public function getFileIds(): array
    {
        return $this->get('ids', []);
    }

    /**
     * Get the collection name from request
     */
    public function getCollection(): string
    {
        return $this->get('collection', 'upload');
    }
}
