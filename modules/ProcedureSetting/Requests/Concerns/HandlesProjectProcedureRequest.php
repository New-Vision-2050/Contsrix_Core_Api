<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\ProcedureSetting\Models\WorkFlow;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;

trait HandlesProjectProcedureRequest
{
    protected function prepareProjectProcedureAliases(): void
    {
        $merge = [];

        if (! $this->has('name') && $this->has('procedure_name')) {
            $merge['name'] = $this->input('procedure_name');
        }

        if (! $this->has('is_active') && $this->has('status')) {
            $merge['is_active'] = $this->input('status');
        }

        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    protected function isProjectProcedureRequest(): bool
    {
        return $this->filled('project_id')
            || $this->input('type') === ProjectProcedureSetting::PROCEDURE_TYPE;
    }

    protected function projectProcedureRules(bool $required): array
    {
        $nameRule = $required ? 'required' : 'sometimes';

        return [
            'project_id' => [$this->filled('parent_id') ? 'nullable' : 'required', 'uuid', $this->tenantOwnedProjectRule()],
            'type' => ['sometimes', 'string', Rule::in([ProjectProcedureSetting::PROCEDURE_TYPE])],
            'form' => [Rule::prohibitedIf($this->filled('form'))],
            'parent_id' => ['sometimes', 'uuid', 'exists:procedure_settings,id'],
            'name' => [$nameRule, 'string', 'max:255'],
            'procedure_name' => ['sometimes', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'boolean'],
            'execute_type' => ['sometimes', 'string', 'in:parallel,sequence'],
            'icon' => ['nullable', 'string', 'max:255'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'deadline_days' => ['nullable', 'integer', 'min:0'],
            'deadline_hours' => ['nullable', 'integer', 'min:0'],
            'escalation_management_hierarchy_id' => ['nullable', 'integer', 'exists:management_hierarchies,id'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'attachment_type_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'attachment_sub_type_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'attachment_sub_sub_type_id' => ['nullable', 'uuid', 'exists:folders,id'],
            'job_attribute_id' => ['nullable', 'uuid', 'exists:project_procedure_job_attributes,id'],
            'used_in_document_cycle' => ['sometimes', 'boolean'],
            'appears_in_archive_after_approval' => ['sometimes', 'boolean'],
            'appears_in_attachments_library' => ['sometimes', 'boolean'],
            'requires_asset_id' => ['sometimes', 'boolean'],
        ];
    }

    public function projectProcedureData(): array
    {
        return array_intersect_key($this->validated(), array_flip($this->projectProcedureKeys()));
    }

    public function projectProcedureMetadataData(): array
    {
        return array_intersect_key($this->validated(), array_flip($this->projectProcedureMetadataKeys()));
    }

    public function projectId(): ?string
    {
        $projectId = $this->validated('project_id') ?? $this->input('project_id');

        if (is_string($projectId) && $projectId !== '') {
            return $projectId;
        }

        return $this->resolveProjectIdFromParent();
    }

    public function parentProcedureSettingId(): ?string
    {
        $parentId = $this->validated('parent_id') ?? $this->input('parent_id');

        return is_string($parentId) && $parentId !== '' ? $parentId : null;
    }

    protected function resolveProjectIdFromParent(): ?string
    {
        $parentId = $this->input('parent_id');

        if (! is_string($parentId) || $parentId === '') {
            return null;
        }

        $parent = ProcedureSetting::withoutGlobalScopes()
            ->where('id', $parentId)
            ->where('type', ProjectProcedureSetting::PROCEDURE_TYPE)
            ->first(['work_flow_id']);

        if ($parent === null) {
            return null;
        }

        $workFlow = WorkFlow::withoutGlobalScopes()
            ->where('id', $parent->work_flow_id)
            ->first(['project_id']);

        $projectId = $workFlow?->project_id;

        return is_string($projectId) && $projectId !== '' ? $projectId : null;
    }

    protected function tenantOwnedProjectRule()
    {
        $tenantId = tenant('id');

        if ($tenantId === null || $tenantId === '') {
            return Rule::exists('projects', 'id');
        }

        $tenantId = (string) $tenantId;
        $morphType = ProjectManagement::class;

        return function (string $attribute, mixed $value, \Closure $fail) use ($tenantId, $morphType) {
            if (! is_string($value) || $value === '') {
                return;
            }

            $exists = DB::table('projects')
                ->where('id', $value)
                ->where(function ($query) use ($tenantId, $morphType, $value) {
                    $query->where('company_id', $tenantId)
                        ->orWhereExists(function ($shareQuery) use ($tenantId, $morphType, $value) {
                            $shareQuery->select(DB::raw(1))
                                ->from('resource_shares')
                                ->where('shareable_type', $morphType)
                                ->where('shareable_id', $value)
                                ->where('shared_with_company_id', $tenantId)
                                ->where('status', 'accepted');
                        });
                })
                ->exists();

            if (! $exists) {
                $fail(__('The selected :attribute is invalid.', ['attribute' => 'project id']));
            }
        };
    }

    private function projectProcedureKeys(): array
    {
        return [
            'name',
            'is_active',
            'execute_type',
            'icon',
            'percentage',
            'deadline_days',
            'deadline_hours',
            'escalation_management_hierarchy_id',
            'sort_order',
        ];
    }

    private function projectProcedureMetadataKeys(): array
    {
        return [
            'attachment_type_id',
            'attachment_sub_type_id',
            'attachment_sub_sub_type_id',
            'job_attribute_id',
            'used_in_document_cycle',
            'appears_in_archive_after_approval',
            'appears_in_attachments_library',
            'requires_asset_id',
        ];
    }
}
