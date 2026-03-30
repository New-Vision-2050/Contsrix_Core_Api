<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProcedureSetting\DTO\CreateProcedureSettingStepDTO;

class CreateProcedureSettingStepRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'        => 'nullable|string|max:255',
            'employee_id' => 'nullable|string|uuid|exists:users,id',
            'is_accept'   => 'nullable|boolean',
            'is_approve'  => 'nullable|boolean',
            'duration'    => 'nullable|integer|min:0',
            'forms'       => 'nullable|string|in:approve,accept,financial',
        ];
    }

    public function createCreateProcedureSettingStepDTO(): CreateProcedureSettingStepDTO
    {
        return new CreateProcedureSettingStepDTO(
            procedure_setting_id: $this->route('procedureSettingId'),
            name:                 $this->get('name'),
            employee_id:          $this->get('employee_id'),
            is_accept:            $this->boolean('is_accept', false),
            is_approve:           $this->boolean('is_approve', false),
            duration:             (int) $this->get('duration', 0),
            forms:                $this->get('forms'),
        );
    }
}
