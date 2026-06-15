<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

class GetProcedureSettingListRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'per_page'    => 'integer',
            'page'        => 'integer',
            'type'        => ['sometimes', 'string', Rule::in(ProcedureSettingType::values())],
            'execute_type'=> ['sometimes', 'string', Rule::in(ProcedureSettingType::values())],
            'work_flow_id'=> 'sometimes|uuid|exists:work_flows,id',
            'branch_id'   => ['sometimes', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
            'parent_id'   => 'sometimes|uuid|exists:procedure_settings,id',
        ];
    }

    public function getFilters(): array
    {
        $filters = [];
        if ($this->filled('type')) {
            $filters['type'] = (string) $this->get('type');
        } elseif ($this->filled('execute_type')) {
            // Backward-compatible alias from client payloads.
            $filters['type'] = (string) $this->get('execute_type');
        }
        if ($this->filled('work_flow_id')) {
            $filters['work_flow_id'] = (string) $this->get('work_flow_id');
        }
        if ($this->filled('branch_id')) {
            $filters['branch_id'] = (int) $this->get('branch_id');
        }
        if ($this->filled('parent_id')) {
            $filters['parent_id'] = (string) $this->get('parent_id');
        }

        return $filters;
    }
}
