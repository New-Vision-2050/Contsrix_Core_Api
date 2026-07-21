<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProcedureSetting\Requests\Concerns\HandlesProjectProcedureRequest;

class GetProcedureSettingRequest extends FormRequest
{
    use HandlesProjectProcedureRequest;

    public function rules(): array
    {
        return [
            'project_id' => ['sometimes', 'uuid', $this->tenantOwnedProjectRule()],
            'parent_id' => ['sometimes', 'uuid', 'exists:procedure_settings,id'],
        ];
    }
}
