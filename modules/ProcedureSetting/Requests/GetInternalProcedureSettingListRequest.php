<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;
use Modules\ProcedureSetting\Requests\Concerns\HandlesProjectProcedureRequest;
use Modules\Project\ProjectManagement\Models\ProjectProcedureSetting;

class GetInternalProcedureSettingListRequest extends FormRequest
{
    use HandlesProjectProcedureRequest;

    public function rules(): array
    {
        $projectIdRule = $this->input('type') === ProjectProcedureSetting::PROCEDURE_TYPE
            ? 'required'
            : 'sometimes';

        $typeValues = $this->filled('project_id')
            ? [ProjectProcedureSetting::PROCEDURE_TYPE]
            : ProcedureSettingType::values();

        return [
            'type' => ['sometimes', 'string', Rule::in($typeValues)],
            'project_id' => [$projectIdRule, 'uuid', $this->tenantOwnedProjectRule()],
            'parent_id' => ['sometimes', 'uuid', 'exists:procedure_settings,id'],
        ];
    }

    public function getType(): ?string
    {
        return $this->filled('type') ? (string) $this->get('type') : null;
    }
}
