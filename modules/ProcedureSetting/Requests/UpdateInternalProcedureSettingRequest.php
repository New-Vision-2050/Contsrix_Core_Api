<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessCondition;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;

class UpdateInternalProcedureSettingRequest extends FormRequest
{
    /**
     * Normalize conditions from frontend indexed format [{key, value}] to
     * backend associative format {allow_on_holidays: true, ...} before
     * validation so that dot-notation sub-key rules resolve correctly.
     */
    protected function prepareForValidation(): void
    {
        $conditions = $this->input('conditions');

        if (! is_array($conditions) || $conditions === []) {
            return;
        }

        $firstKey = array_key_first($conditions);
        if (! is_int($firstKey)) {
            return;
        }

        $normalized = [];
        foreach ($conditions as $item) {
            if (! is_array($item) || ! array_key_exists('key', $item)) {
                continue;
            }

            $snakeKey = Str::snake((string) $item['key']);
            $enum     = InternalProcessCondition::tryFrom($snakeKey);
            $key      = $enum !== null ? $enum->value : $snakeKey;

            $normalized[$key] = $item['value'] ?? null;
        }

        $this->merge(['conditions' => $normalized]);
    }

    public function rules(): array
    {
        $formKey = $this->resolveFormKey();

        return array_merge([
            'name'              => ['sometimes', 'string', 'max:255'],
            'type'              => ['sometimes', 'string', Rule::in(ProcedureSettingType::values())],
            'form'              => ['sometimes', 'string', Rule::in(InternalProcessForm::values())],
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
        ], InternalProcessCondition::validationRulesForForm($formKey, 'conditions'));
    }

    private function resolveFormKey(): ?string
    {
        $form = $this->input('form');
        if (is_string($form) && $form !== '') {
            return $form;
        }

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
