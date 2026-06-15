<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class CreateInternalProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return array_merge([
            'name'              => ['sometimes', 'string', 'max:255'],
            'form'              => ['required', 'string', Rule::in(InternalProcessForm::values())],
            'execute_type'      => ['sometimes', 'string', 'in:parallel,sequence'],
            'conditions'        => ['sometimes', 'array'],
            'appears_before_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'appears_after_id'  => ['nullable', 'uuid', 'exists:procedure_settings,id'],
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
