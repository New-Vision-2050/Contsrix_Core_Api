<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectRequirement;

class CreateProjectRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requirements' => ['required', 'array', 'min:1'],
            'requirements.*.requirement_code' => ['required', 'string', 'max:255'],
            'requirements.*.required_document_name' => ['required', 'string', 'max:255'],
            'requirements.*.document' => ['required', 'string', 'max:255'],
            'requirements.*.document_type_id' => ['nullable', 'uuid', $this->tenantScopedDocumentTypeRule()],
            'requirements.*.document_type' => ['required', 'string', 'max:255'],
            'requirements.*.specialization_id' => ['nullable', 'uuid', Rule::exists('academic_specializations', 'id')],
            'requirements.*.specialization' => ['nullable', 'string', 'max:255'],
            'requirements.*.stage' => ['nullable', 'string', 'max:255'],
            'requirements.*.sending_entity_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')],
            'requirements.*.sending_entity' => ['nullable', 'string', 'max:255'],
            'requirements.*.review_entity_id' => ['nullable', 'uuid', Rule::exists('companies', 'id')],
            'requirements.*.review_entity' => ['nullable', 'string', 'max:255'],
            'requirements.*.receiver_company_ids' => ['nullable', 'array'],
            'requirements.*.receiver_company_ids.*' => ['required', 'uuid', 'distinct', Rule::exists('companies', 'id')],
            'requirements.*.repetition' => ['required', 'string', Rule::in(ProjectRequirementRepetition::values())],
            'requirements.*.repetition_interval_type' => ['nullable', 'string', Rule::in(['day', 'week', 'month'])],
            'requirements.*.repeat_days' => ['nullable', 'array'],
            'requirements.*.evaluation_status' => ['nullable', 'string', Rule::in(ProjectRequirementEvaluationStatus::values())],
            'requirements.*.resulting_document' => ['nullable', 'string', 'max:255'],
            'requirements.*.completion_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
        ];
    }

    public function rows(): array
    {
        return array_values($this->validated()['requirements'] ?? []);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('requirements')) {
            return;
        }

        $row = $this->only([
            'requirement_code',
            'required_document_name',
            'document',
            'document_type_id',
            'document_type',
            'specialization_id',
            'specialization',
            'stage',
            'sending_entity_id',
            'sending_entity',
            'review_entity_id',
            'review_entity',
            'receiver_company_ids',
            'repetition',
            'repetition_interval_type',
            'repeat_days',
            'evaluation_status',
            'resulting_document',
            'completion_percentage',
        ]);

        if ($row !== []) {
            $this->merge(['requirements' => [$row]]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSubmittedCodes($validator);
            $this->validateRepeatDays($validator);
            $this->validateIntervalTypes($validator);
        });
    }

    protected function tenantScopedDocumentTypeRule()
    {
        $tenantId = tenant('id');

        return Rule::exists('document_types', 'id')->where(static function ($query) use ($tenantId) {
            if ($tenantId !== null && $tenantId !== '') {
                $query->where('company_id', (string) $tenantId);
            }

            return $query->whereNull('deleted_at');
        });
    }

    protected function routeProjectId(): string
    {
        return (string) ($this->route('project') ?? $this->route('project_id') ?? $this->input('project_id'));
    }

    private function validateSubmittedCodes(Validator $validator): void
    {
        $codes = [];
        $projectId = $this->routeProjectId();

        foreach ($this->input('requirements', []) as $index => $row) {
            $code = trim((string) ($row['requirement_code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $normalizedCode = mb_strtolower($code);
            if (isset($codes[$normalizedCode])) {
                $validator->errors()->add(
                    "requirements.{$index}.requirement_code",
                    'The requirement code must be unique inside this request.'
                );
            }

            $codes[$normalizedCode] = $code;
        }

        if ($codes === [] || $projectId === '') {
            return;
        }

        $existingCodes = ProjectRequirement::query()
            ->withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->whereIn('requirement_code', array_values($codes))
            ->pluck('requirement_code')
            ->map(static fn (string $code): string => mb_strtolower($code))
            ->all();

        foreach ($this->input('requirements', []) as $index => $row) {
            $code = mb_strtolower(trim((string) ($row['requirement_code'] ?? '')));

            if ($code !== '' && in_array($code, $existingCodes, true)) {
                $validator->errors()->add(
                    "requirements.{$index}.requirement_code",
                    'The requirement code has already been taken for this project.'
                );
            }
        }
    }

    private function validateRepeatDays(Validator $validator): void
    {
        foreach ($this->input('requirements', []) as $index => $row) {
            $repetition = $row['repetition'] ?? null;

            if (! array_key_exists('repeat_days', $row) || $row['repeat_days'] === null) {
                continue;
            }

            if (! in_array($repetition, [
                ProjectRequirementRepetition::Daily->value,
                ProjectRequirementRepetition::Weekly->value,
            ], true)) {
                $validator->errors()->add(
                    "requirements.{$index}.repeat_days",
                    'Repeat days are only allowed for daily or weekly repetition.'
                );

                continue;
            }

            foreach ((array) $row['repeat_days'] as $dayIndex => $day) {
                if (! is_string($day) && ! is_int($day)) {
                    $validator->errors()->add(
                        "requirements.{$index}.repeat_days.{$dayIndex}",
                        'Each repeat day must be a string or integer.'
                    );
                }
            }
        }
    }

    private function validateIntervalTypes(Validator $validator): void
    {
        foreach ($this->input('requirements', []) as $index => $row) {
            $repetition = $row['repetition'] ?? null;
            $intervalType = $row['repetition_interval_type'] ?? null;

            if ($intervalType === null || $intervalType === '') {
                continue;
            }

            $expected = ProjectRequirementRepetition::intervalTypeFor((string) $repetition);
            if ($expected !== null && $intervalType !== $expected) {
                $validator->errors()->add(
                    "requirements.{$index}.repetition_interval_type",
                    "The repetition interval type must be {$expected} for {$repetition} repetition."
                );
            }
        }
    }
}
