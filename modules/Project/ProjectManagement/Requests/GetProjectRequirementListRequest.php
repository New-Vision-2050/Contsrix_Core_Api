<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementEvaluationStatus;
use Modules\Project\ProjectManagement\Enums\ProjectRequirementRepetition;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class GetProjectRequirementListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'document_type_id' => ['prohibited'],
            'procedure_setting_id' => ['nullable', 'uuid', $this->projectProcedureSettingRule()],
            'document_type' => ['nullable', 'string', 'max:255'],
            'specialization_id' => ['nullable', 'uuid'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'stage' => ['nullable', 'string', 'max:255'],
            'sending_entity_id' => ['nullable', 'uuid'],
            'sending_entity' => ['nullable', 'string', 'max:255'],
            'review_entity_id' => ['nullable', 'uuid'],
            'review_entity' => ['nullable', 'string', 'max:255'],
            'receiver_company_id' => ['nullable', 'uuid'],
            'evaluation_status' => ['nullable', 'string', Rule::in(ProjectRequirementEvaluationStatus::values())],
            'repetition' => ['nullable', 'string', Rule::in(ProjectRequirementRepetition::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        return array_filter(
            $this->safe()->only([
                'search',
                'procedure_setting_id',
                'document_type',
                'specialization_id',
                'specialization',
                'stage',
                'sending_entity_id',
                'sending_entity',
                'review_entity_id',
                'review_entity',
                'receiver_company_id',
                'evaluation_status',
                'repetition',
            ]),
            static fn ($value): bool => $value !== null && $value !== ''
        );
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 15);
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
}
