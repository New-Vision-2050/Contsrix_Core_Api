<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\ProcedureSetting\Commands\UpdateProcedureSettingStepCommand;

class UpdateProcedureSettingStepRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'employee_id' => 'nullable|string|uuid|exists:users,id',
            'is_accept'   => 'nullable|boolean',
            'is_approve'  => 'nullable|boolean',
            'duration'    => 'nullable|integer|min:0',
            'forms'       => 'nullable|string|in:approve,accept,financial',
        ];
    }

    public function createUpdateProcedureSettingStepCommand(): UpdateProcedureSettingStepCommand
    {
        return new UpdateProcedureSettingStepCommand(
            id:          (int) $this->route('stepId'),
            employee_id: $this->get('employee_id'),
            is_accept:   $this->has('is_accept') ? $this->boolean('is_accept') : null,
            is_approve:  $this->has('is_approve') ? $this->boolean('is_approve') : null,
            duration:    $this->has('duration') ? (int) $this->get('duration') : null,
            forms:       $this->get('forms'),
        );
    }
}
