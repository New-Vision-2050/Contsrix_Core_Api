<?php

declare(strict_types=1);

namespace Modules\Shared\InternalProcessType\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Shared\InternalProcessType\DTO\UpdateInternalProcessTypeDTO;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

class UpdateInternalProcessTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name'       => ['sometimes', 'string', 'max:255'],
            'is_active'  => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'settings'   => ['sometimes', 'array'],
        ], InternalProcessCondition::validationRules());
    }

    public function createDTO(string $id): UpdateInternalProcessTypeDTO
    {
        return new UpdateInternalProcessTypeDTO(
            id: $id,
            name: $this->input('name'),
            settings: $this->has('settings') ? $this->input('settings') : null,
            isActive: $this->has('is_active') ? (bool) $this->input('is_active') : null,
            sortOrder: $this->has('sort_order') ? (int) $this->input('sort_order') : null,
        );
    }
}
