<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;

class UpdateInternalProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        $formKey = $this->resolveFormKey();

        return array_merge([
            'name'              => ['sometimes', 'string', 'max:255'],
            'execute_type'      => ['sometimes', 'string', 'in:parallel,sequence'],
            'conditions'        => ['sometimes', 'array'],
            'appears_before_id' => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'appears_after_id'  => ['nullable', 'uuid', 'exists:procedure_settings,id'],
            'sort_order'        => ['sometimes', 'integer', 'min:0'],
            'percentage'        => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deadline_days'     => ['nullable', 'integer', 'min:0'],
            'deadline_hours'    => ['nullable', 'integer', 'min:0'],
        ], InternalProcessCondition::validationRulesForForm($formKey, 'conditions'));
    }

    private function resolveFormKey(): ?string
    {
        $id = $this->route('internalProcedureId');
        if (! is_string($id) || $id === '') {
            return null;
        }

        return ProcedureSetting::query()->find($id)?->form;
    }

    public function toData(): array
    {
        return $this->validated();
    }
}
