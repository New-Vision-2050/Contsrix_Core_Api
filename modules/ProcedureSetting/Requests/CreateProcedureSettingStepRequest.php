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

            // When action_taker_type is "himself" or "assigned_user" only the "approve" form is permitted.
            'forms' => [
                'nullable',
                'string',
                'in:approve,accept,financial',
                Rule::when(
                    in_array($this->input('action_taker_type'), ['himself', 'assigned_user'], true),
                    ['in:approve'],
                ),
            ],

            'branch_id'      => ['nullable', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
            'management_id' => 'nullable|integer|exists:management_hierarchies,id',

            'is_view_only'                     => 'nullable|boolean',
            'is_return_with_notes'             => 'nullable|boolean',
            'requires_approval_within_period' => 'nullable|boolean',
            'approval_within_days'            => 'nullable|integer|min:0',
            'approval_within_hours'           => 'nullable|integer|min:0',

            'notify_by_email'    => 'nullable|boolean',
            'notify_by_whatsapp' => 'nullable|boolean',
            'notify_by_sms'      => 'nullable|boolean',
            'notify_by_push'     => 'nullable|boolean',
            'skipping_period'    => 'nullable|integer|min:0',

            'escalation_management_hierarchy_id' => 'nullable|integer|exists:management_hierarchies,id',

            'step_order' => 'nullable|integer|min:0',

            'action_taker_type' => 'nullable|string|in:specific_user,management_hierarchy,specific_procedures,himself,assigned_user',

            // ── Deprecated (legacy) ──────────────────────────────────────────
            // Replaced by action_taker_management_hierarchies array of objects.
            // Still accepted for backward compatibility but not required.
            'action_taker_management_hierarchy_type' => [
                'nullable',
                'string',
                'in:branch_manager,management_manager,project_manager,deputy_manager',
                'prohibited_unless:action_taker_type,management_hierarchy',
            ],

            'action_taker_alternative_management_hierarchy_type' => [
                'nullable',
                'array',
                'prohibited_unless:action_taker_type,management_hierarchy',
            ],
            'action_taker_alternative_management_hierarchy_type.*' => [
                'string',
                'in:branch_manager,management_manager,deputy_manager',
            ],

            // ── New format (canonical) ───────────────────────────────────────
            // Array of {action_taker_management_hierarchy_type, is_Deputy_Director} objects.
            // Required when action_taker_type === "management_hierarchy".
            'action_taker_management_hierarchies' => [
                'nullable',
                'array',
                'max:3',
                'required_if:action_taker_type,management_hierarchy',
                'prohibited_unless:action_taker_type,management_hierarchy',
            ],
            'action_taker_management_hierarchies.*.action_taker_management_hierarchy_type' => [
                'required',
                'string',
                'in:project_manager,branch_manager,management_manager',
            ],
            'action_taker_management_hierarchies.*.is_Deputy_Director' => [
                'nullable',
                'boolean',
            ],

            // Parallel arrays: type[i]+id[i] define each specific-procedure target.
            'action_taker_specific_procedure_type' => [
                'nullable',
                'array',
                'required_if:action_taker_type,specific_procedures',
                'prohibited_unless:action_taker_type,specific_procedures',
            ],
            'action_taker_specific_procedure_type.*' => 'string|in:branch,management,job_title,job_role',

            'action_taker_specific_procedure_id' => [
                'nullable',
                'array',
                'required_if:action_taker_type,specific_procedures',
                'prohibited_unless:action_taker_type,specific_procedures',
            ],
            'action_taker_specific_procedure_id.*' => 'string',

            'action_taker_user_ids'   => [
                'nullable',
                'array',
                'required_if:action_taker_type,specific_user',
                new ActionTakerUserIdsUniquePerProcedureSetting((string) $this->route('procedureSettingId')),
            ],
            'action_taker_user_ids.*' => 'uuid|exists:users,id',
            'concerned_management_hierarchy_ids'  => 'nullable|array',
            'concerned_management_hierarchy_ids.*' => 'integer|exists:management_hierarchies,id',
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
            notify_by_sms:        (bool) ($v['notify_by_sms'] ?? false),
            notify_by_push:       (bool) ($v['notify_by_push'] ?? false),
            skipping_period:      isset($v['skipping_period']) ? (int) $v['skipping_period'] : null,
            escalation_management_hierarchy_id: isset($v['escalation_management_hierarchy_id']) ? (int) $v['escalation_management_hierarchy_id'] : null,
            step_order:            isset($v['step_order']) ? (int) $v['step_order'] : null,
            action_taker_type:     $v['action_taker_type'] ?? null,
            action_taker_management_hierarchy_type: $v['action_taker_management_hierarchy_type'] ?? null,
            action_taker_alternative_management_hierarchy_type: isset($v['action_taker_alternative_management_hierarchy_type']) ? (array) $v['action_taker_alternative_management_hierarchy_type'] : null,
            action_taker_management_hierarchies: isset($v['action_taker_management_hierarchies']) ? (array) $v['action_taker_management_hierarchies'] : null,
            action_taker_specific_procedure_type: isset($v['action_taker_specific_procedure_type']) ? (array) $v['action_taker_specific_procedure_type'] : null,
            action_taker_specific_procedure_id: isset($v['action_taker_specific_procedure_id']) ? (array) $v['action_taker_specific_procedure_id'] : null,
            action_taker_user_ids: $v['action_taker_user_ids'] ?? null,
            concerned_management_hierarchy_ids: $v['concerned_management_hierarchy_ids'] ?? null,
        );
    }
}
