<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ramsey\Uuid\Uuid;
use Modules\ProcedureSetting\Commands\UpdateProcedureSettingCommand;
use Modules\ProcedureSetting\Enums\ProcedureSettingType;

class UpdateProcedureSettingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'         => 'required|string|max:255',
            'type'         => ['required', 'string', Rule::in(ProcedureSettingType::values())],
            'execute_type' => 'required|string|in:parallel,sequence',
            'icon'         => 'nullable|string|max:255',
            'percentage'   => 'nullable|numeric|min:0|max:100',
            'stage_duration_percentage' => 'nullable|numeric|min:0|max:100',
            'is_sequential' => 'sometimes|boolean',
            'is_parallel'   => 'sometimes|boolean',
            'deadline_days' => 'nullable|integer|min:0',
            'deadline_hours' => 'nullable|integer|min:0',
            'time_limit_days' => 'nullable|integer|min:0',
            'time_limit_hours' => 'nullable|integer|min:0',
            'escalation_user_id' => 'nullable|uuid|exists:users,id',
            'work_flow_id'       => 'nullable|uuid|exists:work_flows,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'type (' . ProcedureSettingType::validationHint() . ')',
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'type must be one of: ' . ProcedureSettingType::validationHint() . '.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $merge = [];
        if (!$this->has('execute_type')) {
            if ($this->boolean('is_sequential')) {
                $merge['execute_type'] = 'sequence';
            } elseif ($this->boolean('is_parallel')) {
                $merge['execute_type'] = 'parallel';
            }
        }
        if ($this->has('stage_duration_percentage') && !$this->has('percentage')) {
            $merge['percentage'] = $this->input('stage_duration_percentage');
        }
        if ($this->has('time_limit_days') && !$this->has('deadline_days')) {
            $merge['deadline_days'] = $this->input('time_limit_days');
        }
        if ($this->has('time_limit_hours') && !$this->has('deadline_hours')) {
            $merge['deadline_hours'] = $this->input('time_limit_hours');
        }
        if ($merge !== []) {
            $this->merge($merge);
        }
    }

    public function createUpdateProcedureSettingCommand(): UpdateProcedureSettingCommand
    {
        return new UpdateProcedureSettingCommand(
            Uuid::fromString($this->route('id')),
            $this->validated(),
        );
    }
}
