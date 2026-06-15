<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\InternalProcessType\DTO\CreateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessEntityType;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\Shared\InternalProcessType\Support\InternalProcessTypePayload;

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
            'form'        => ['required', 'string', Rule::in(InternalProcessForm::values())],
            'conditions'  => ['required', 'array'],
            'ordering'    => ['sometimes', 'array'],
            'ordering.' . InternalProcessTypePayload::APPEARS_BEFORE_KEY => ['nullable', 'uuid', 'exists:internal_process_types,id'],
            'ordering.' . InternalProcessTypePayload::APPEARS_AFTER_KEY  => ['nullable', 'uuid', 'exists:internal_process_types,id'],
            'is_active'   => ['sometimes', 'boolean'],
            'sort_order'  => ['sometimes', 'integer', 'min:0'],
        ], InternalProcessCondition::validationRulesForForm($this->input('form'), 'conditions'));
    }

    public function createDTO(): CreateInternalProcessTypeDTO
    {
        return new CreateInternalProcessTypeDTO(
            entityType: $this->input('entity_type'),
            name: $this->input('name'),
            form: $this->input('form'),
            conditions: $this->input('conditions', []),
            ordering: $this->input('ordering', []),
            isActive: (bool) $this->input('is_active', true),
            sortOrder: (int) $this->input('sort_order', 0),
        );
    }
}
