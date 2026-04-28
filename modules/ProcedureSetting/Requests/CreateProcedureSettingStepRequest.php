<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\ProcedureSetting\DTO\CreateProcedureSettingStepDTO;
use Modules\ProcedureSetting\Rules\ActionTakerUserIdsUniquePerProcedureSetting;

class CreateProcedureSettingStepRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('approval_within_s') && ! $this->has('approval_within_hours')) {
            $this->merge([
                'approval_within_hours' => $this->input('approval_within_s'),
            ]);
        }

        $this->merge([
            'procedure_setting_route_id' => $this->route('procedureSettingId'),
        ]);
    }

    public function rules(): array
    {
        $procedureSettingExists = Rule::exists('procedure_settings', 'id');
        if (tenancy()->initialized && tenant('id')) {
            $procedureSettingExists = $procedureSettingExists->where(
                'company_id',
                (string) tenant('id'),
            );
        }

        return [
            'procedure_setting_route_id' => ['required', 'uuid', $procedureSettingExists],

            'procedure_setting_id' => [
                'sometimes',
                'uuid',
                Rule::in([(string) $this->route('procedureSettingId')]),
            ],

            'name'        => 'nullable|string|max:255',
            'is_accept'   => 'nullable|boolean',
            'is_approve'  => 'nullable|boolean',
            'forms'       => 'nullable|string|in:approve,accept,financial',

            'branch_id'      => ['nullable', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
            'management_id' => 'nullable|integer|exists:management_hierarchies,id',

            'is_view_only'                     => 'nullable|boolean',
            'is_return_with_notes'             => 'nullable|boolean',
            'requires_approval_within_period' => 'nullable|boolean',
            'approval_within_days'            => 'nullable|integer|min:0',
            'approval_within_hours'           => 'nullable|integer|min:0',

            'notify_by_email'    => 'nullable|boolean',
            'notify_by_whatsapp' => 'nullable|boolean',

            'escalation_user_id' => 'nullable|uuid|exists:users,id',

            'action_taker_user_ids'   => [
                'nullable',
                'array',
                new ActionTakerUserIdsUniquePerProcedureSetting((string) $this->route('procedureSettingId')),
            ],
            'action_taker_user_ids.*' => 'uuid|exists:users,id',
            'concerned_user_ids'      => 'nullable|array',
            'concerned_user_ids.*'    => 'uuid|exists:users,id',
        ];
    }

    public function attributes(): array
    {
        return [
            'procedure_setting_route_id' => 'procedure setting',
        ];
    }

    public function createCreateProcedureSettingStepDTO(): CreateProcedureSettingStepDTO
    {
        $v = $this->validated();

        return new CreateProcedureSettingStepDTO(
            procedure_setting_id: (string) $this->route('procedureSettingId'),
            name:                 $v['name'] ?? null,
            is_accept:            (bool) ($v['is_accept'] ?? false),
            is_approve:           (bool) ($v['is_approve'] ?? false),
            forms:                $v['forms'] ?? null,
            branch_id:            isset($v['branch_id']) ? (int) $v['branch_id'] : null,
            management_id:        isset($v['management_id']) ? (int) $v['management_id'] : null,
            is_view_only:         (bool) ($v['is_view_only'] ?? false),
            is_return_with_notes: (bool) ($v['is_return_with_notes'] ?? false),
            requires_approval_within_period: (bool) ($v['requires_approval_within_period'] ?? false),
            approval_within_days: isset($v['approval_within_days']) ? (int) $v['approval_within_days'] : null,
            approval_within_hours: isset($v['approval_within_hours']) ? (int) $v['approval_within_hours'] : null,
            notify_by_email:      (bool) ($v['notify_by_email'] ?? false),
            notify_by_whatsapp:   (bool) ($v['notify_by_whatsapp'] ?? false),
            escalation_user_id:   isset($v['escalation_user_id']) ? (string) $v['escalation_user_id'] : null,
            action_taker_user_ids: $v['action_taker_user_ids'] ?? null,
            concerned_user_ids:   $v['concerned_user_ids'] ?? null,
        );
    }
}
