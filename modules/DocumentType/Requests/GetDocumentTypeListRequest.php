<?php

declare(strict_types=1);

namespace Modules\DocumentType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;

class GetDocumentTypeListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page' => 'integer',
            'page' => 'integer',
            'name' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function getFilters(): array
    {
        return $this->only(['name', 'is_active']);
    }
}
