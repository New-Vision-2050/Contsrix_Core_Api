<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class CreateInternalProcedureSettingRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $map = [
            'appears_before_ids' => 'appears_before_id',
            'appears_after_ids'  => 'appears_after_id',
        ];

        $merge = [];
        foreach ($map as $plural => $singular) {
            if ($this->has($plural) && ! $this->has($singular)) {
                $merge[$singular] = $this->input($plural);
            }
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function rules(): array
    {
        return array_merge([
            'name'              => ['sometimes', 'string', 'max:255'],
            'type'              => ['required', 'string', Rule::in(ProcedureSettingType::values())],
            'form'              => ['required', 'string', Rule::in(InternalProcessForm::values())],
            'is_active'         => ['sometimes', 'boolean'],
            'execute_type'      => ['sometimes', 'string', 'in:parallel,sequence'],
            'conditions'        => ['sometimes', 'array'],
            'appears_before_id'    => ['nullable', 'array'],
            'appears_before_id.*'  => ['uuid', 'exists:procedure_settings,id'],
            'appears_after_id'     => ['nullable', 'array'],
            'appears_after_id.*'   => ['uuid', 'exists:procedure_settings,id'],
            'sort_order'        => ['sometimes', 'integer', 'min:0'],
            'percentage'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deadline_days'     => ['nullable', 'integer', 'min:0'],
            'deadline_hours'    => ['nullable', 'integer', 'min:0'],
        ], InternalProcessCondition::validationRulesForForm($this->input('form'), 'conditions'));
    }

    public function toData(): array
    {
        return $this->validated();
    }
}
