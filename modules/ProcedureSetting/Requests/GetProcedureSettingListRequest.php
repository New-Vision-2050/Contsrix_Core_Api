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
            'work_flow_id'=> 'sometimes|uuid|exists:work_flows,id',
            'branch_id'   => 'sometimes|array',
            'branch_id.*' => ['integer', 'distinct', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
        ];
    }

    public function getFilters(): array
    {
        $filters = [];
        if ($this->filled('type')) {
            $filters['type'] = (string) $this->get('type');
        }
        if ($this->filled('work_flow_id')) {
            $filters['work_flow_id'] = (string) $this->get('work_flow_id');
        }
        if (is_array($this->get('branch_id')) && $this->get('branch_id') !== []) {
            $filters['branch_id'] = array_values(array_unique(array_map('intval', $this->get('branch_id'))));
        }

        return $filters;
    }
}
