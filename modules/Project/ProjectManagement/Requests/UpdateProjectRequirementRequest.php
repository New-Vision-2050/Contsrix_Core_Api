<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;

class UpdateProjectRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirement_code' => ['sometimes', 'required', 'string', 'max:255'],
            'required_document_name' => ['sometimes', 'required', 'string', 'max:255'],
            'document' => ['sometimes', 'required', 'string', 'max:255'],
            'document_type_id' => ['prohibited'],
            'procedure_setting_id' => ['nullable', 'uuid', $this->projectProcedureSettingRule()],
            'document_type' => ['sometimes', 'required', 'string', 'max:255'],
            'specialization_id' => ['nullable', 'uuid', Rule::exists('academic_specializations', 'id')],
            'specialization' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:255'],
            'sending_entity_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')],
            'sending_entity' => ['nullable', 'string', 'max:255'],
            'review_entity_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')],
            'review_entity' => ['nullable', 'string', 'max:255'],
            'receiver_company_ids' => ['sometimes', 'array'],
            'receiver_company_ids.*' => ['required', 'uuid', 'distinct', Rule::exists('companies', 'id')],
            'repetition' => ['sometimes', 'required', 'string', Rule::in(ProjectRequirementRepetition::values())],
            'repetition_interval_type' => ['nullable', 'string', Rule::in(['day', 'week', 'month'])],
            'repeat_days' => ['nullable', 'array'],
            'evaluation_status' => ['nullable', 'string', Rule::in(ProjectRequirementEvaluationStatus::values())],
            'resulting_document' => ['nullable', 'string', 'max:255'],
            'completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function validatedData(): array
    {
        return $this->validated();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateUniqueRequirementCode($validator);
            $this->validateRepeatDays($validator);
            $this->validateIntervalType($validator);
        });
    }

    private function projectProcedureSettingRule()
    {
        $projectId = (string) $this->route('project');
        $companyId = $this->projectOwnerCompanyId($projectId) ?? tenant('id');

        return Rule::exists('project_procedure_settings', 'procedure_setting_id')
            ->where(static function ($query) use ($projectId, $companyId) {
                if ($projectId !== '') {
                    $query->where('project_id', $projectId);
                }

                if ($companyId !== null && $companyId !== '') {
                    $query->where('company_id', (string) $companyId);
                }

                return $query;
            });
    }

    private function projectOwnerCompanyId(string $projectId): ?string
    {
        if ($projectId === '') {
            return null;
        }

        $companyId = ProjectManagement::query()
            ->withoutGlobalScopes()
            ->where('id', $projectId)
            ->value('company_id');

        return $companyId === null ? null : (string) $companyId;
    }

    private function validateUniqueRequirementCode(Validator $validator): void
    {
        if (! $this->filled('requirement_code')) {
            return;
        }

        $exists = ProjectRequirement::query()
            ->withoutGlobalScopes()
            ->where('project_id', (string) $this->route('project'))
            ->where('requirement_code', trim((string) $this->input('requirement_code')))
            ->where('id', '!=', (string) $this->route('requirement'))
            ->exists();

        if ($exists) {
            $validator->errors()->add(
                'requirement_code',
                'The requirement code has already been taken for this project.'
            );
        }
    }

    private function validateRepeatDays(Validator $validator): void
    {
        if (! $this->has('repeat_days') || $this->input('repeat_days') === null) {
            return;
        }

        $repetition = $this->input('repetition');
        if ($repetition !== null && ! in_array($repetition, [
            ProjectRequirementRepetition::Daily->value,
            ProjectRequirementRepetition::Weekly->value,
        ], true)) {
            $validator->errors()->add(
                'repeat_days',
                'Repeat days are only allowed for daily or weekly repetition.'
            );

            return;
        }

        foreach ((array) $this->input('repeat_days') as $index => $day) {
            if (! is_string($day) && ! is_int($day)) {
                $validator->errors()->add(
                    "repeat_days.{$index}",
                    'Each repeat day must be a string or integer.'
                );
            }
        }
    }

    private function validateIntervalType(Validator $validator): void
    {
        if (! $this->filled('repetition') || ! $this->filled('repetition_interval_type')) {
            return;
        }

        $expected = ProjectRequirementRepetition::intervalTypeFor((string) $this->input('repetition'));
        if ($expected !== null && $this->input('repetition_interval_type') !== $expected) {
            $validator->errors()->add(
                'repetition_interval_type',
                "The repetition interval type must be {$expected} for {$this->input('repetition')} repetition."
            );
        }
    }
}
