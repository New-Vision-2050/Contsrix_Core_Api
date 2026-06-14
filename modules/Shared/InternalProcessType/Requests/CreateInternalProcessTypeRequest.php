<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\InternalProcessType\DTO\CreateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessEntityType;

class CreateInternalProcessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'entity_type' => ['required', 'string', Rule::in(InternalProcessEntityType::values())],
            'name'        => ['required', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
            'settings'    => ['sometimes', 'array'],
        ], InternalProcessCondition::validationRules());
    }

    public function createDTO(): CreateInternalProcessTypeDTO
    {
        return new CreateInternalProcessTypeDTO(
            entityType: $this->input('entity_type'),
            name: $this->input('name'),
            settings: $this->input('settings', []),
            isActive: (bool) $this->input('is_active', true),
            sortOrder: (int) $this->input('sort_order', 0),
        );
    }
}
