<?php

declare(strict_types=1);

namespace Modules\DocumentType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\DocumentType\DTO\CreateDocumentTypeDTO;

class CreateDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function createCreateDocumentTypeDTO(): CreateDocumentTypeDTO
    {
        return new CreateDocumentTypeDTO(
            name: $this->get('name'),
            is_active: (int) $this->get('is_active', 1),
            company_id: tenant('id')
        );
    }
}
