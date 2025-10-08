<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetFilesWithWidgetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id' => 'nullable|string|uuid|exists:folders,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.uuid' => 'The folder ID must be a valid UUID.',
            'parent_id.exists' => 'The selected folder does not exist.',
            'page.integer' => 'The page must be an integer.',
            'page.min' => 'The page must be at least 1.',
            'per_page.integer' => 'The per page must be an integer.',
            'per_page.min' => 'The per page must be at least 1.',
            'per_page.max' => 'The per page may not be greater than 100.',
        ];
    }

    public function getFolderId(): ?string
    {
        return $this->input('parent_id');
    }

    public function getPage(): ?int
    {
        return $this->input('page') ? (int) $this->input('page') : null;
    }

    public function getPerPage(): int
    {
        return $this->input('per_page') ? (int) $this->input('per_page') : 10;
    }
}
