<?php

declare(strict_types=1);

namespace Modules\DocumentType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ramsey\Uuid\Uuid;
use Modules\DocumentType\Commands\UpdateDocumentTypeCommand;
use Modules\DocumentType\Handlers\UpdateDocumentTypeHandler;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function createUpdateDocumentTypeCommand(): UpdateDocumentTypeCommand
    {
        return new UpdateDocumentTypeCommand(
            id: Uuid::fromString($this->route('id')),
            name: $this->get('name'),
            is_active: (int)$this->get('is_active'),
        );
    }
}
