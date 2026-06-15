<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\InternalProcessType\DTO\UpdateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use Modules\Shared\InternalProcessType\Models\InternalProcessType;
use Modules\Shared\InternalProcessType\Support\InternalProcessTypePayload;

class UpdateInternalProcessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $formKey = $this->resolveFormKey();

        return array_merge([
            'name'       => ['sometimes', 'string', 'max:255'],
            'form'       => ['sometimes', 'string', Rule::in(InternalProcessForm::values())],
            'conditions' => ['sometimes', 'array'],
            'ordering'   => ['sometimes', 'array'],
            'ordering.' . InternalProcessTypePayload::APPEARS_BEFORE_KEY => ['nullable', 'uuid', 'exists:internal_process_types,id'],
            'ordering.' . InternalProcessTypePayload::APPEARS_AFTER_KEY  => ['nullable', 'uuid', 'exists:internal_process_types,id'],
            'is_active'  => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ], InternalProcessCondition::validationRulesForForm($formKey, 'conditions'));
    }

    private function resolveFormKey(): ?string
    {
        if ($this->filled('form')) {
            return (string) $this->input('form');
        }

        $id = $this->route('id');
        if (! is_string($id) || $id === '') {
            return null;
        }

        $existing = InternalProcessType::query()->find($id);
        if ($existing === null) {
            return null;
        }

        return InternalProcessTypePayload::unpack($existing->settings)['form'];
    }

    public function createDTO(string $id): UpdateInternalProcessTypeDTO
    {
        return new UpdateInternalProcessTypeDTO(
            id: $id,
            name: $this->input('name'),
            form: $this->input('form'),
            conditions: $this->has('conditions') ? $this->input('conditions') : null,
            ordering: $this->has('ordering') ? $this->input('ordering') : null,
            isActive: $this->has('is_active') ? (bool) $this->input('is_active') : null,
            sortOrder: $this->has('sort_order') ? (int) $this->input('sort_order') : null,
        );
    }
}
