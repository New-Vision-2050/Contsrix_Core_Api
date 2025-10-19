<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use BasePackage\Shared\Http\Requests\FormRequest;

class ManageFavouritesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|string|uuid|exists:files,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'ids.required' => __('validation.required', ['attribute' => __('File IDs')]),
            'ids.array' => __('validation.array', ['attribute' => __('File IDs')]),
            'ids.min' => __('validation.min.array', ['attribute' => __('File IDs'), 'min' => 1]),
            'ids.*.required' => __('validation.required', ['attribute' => __('File ID')]),
            'ids.*.string' => __('validation.string', ['attribute' => __('File ID')]),
            'ids.*.uuid' => __('validation.uuid', ['attribute' => __('File ID')]),
            'ids.*.exists' => __('validation.exists', ['attribute' => __('File ID')]),
        ];
    }

    /**
     * Get the file IDs from the request.
     *
     * @return array
     */
    public function getFileIds(): array
    {
        return (array) $this->input('ids', []);
    }
}
