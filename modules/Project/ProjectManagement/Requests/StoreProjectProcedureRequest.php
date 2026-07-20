<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class StoreProjectProcedureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge($this->procedureRules(required: true), $this->metadataRules());
    }

    public function procedureData(): array
    {
        return Arr::only($this->validated(), $this->procedureKeys());
    }

    public function metadataData(): array
    {
        return Arr::only($this->validated(), $this->metadataKeys());
    }

    protected function prepareForValidation(): void
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

    protected function procedureRules(bool $required): array
    {
        $nameRule = $required ? 'required' : 'sometimes';

        return [
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
        ];
    }

    protected function metadataRules(): array
    {
        return [
            'receiver_company_id' => ['nullable', 'uuid', 'exists:companies,id'],
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

    protected function procedureKeys(): array
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

    protected function metadataKeys(): array
    {
        return [
            'receiver_company_id',
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
