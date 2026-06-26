<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Modules\ProcedureSetting\Commands\UpdateProcedureSettingStepCommand;
use Modules\ProcedureSetting\Rules\ActionTakerUserIdsUniquePerProcedureSetting;

class UpdateProcedureSettingStepRequest extends FormRequest
{
    /** Keys merged only for validation; never sent to the update handler. */
    private const INTERNAL_RULE_KEYS = [
        'procedure_setting_route_id',
        'procedure_setting_step_route_id',
        'procedure_setting_id',
    ];

    protected function prepareForValidation(): void
    {
        $this->mergeApprovalWithinHoursFromShortKey();
        $this->mergeRouteBindingForValidation();
    }

    public function rules(): array
    {
        return array_merge($this->routeBindingRules(), $this->payloadRules());
    }

    public function attributes(): array
    {
        return [
            'procedure_setting_route_id'      => 'procedure setting',
            'procedure_setting_step_route_id' => 'procedure setting step',
        ];
    }

    public function createUpdateProcedureSettingStepCommand(): UpdateProcedureSettingStepCommand
    {
        return new UpdateProcedureSettingStepCommand(
            (int) $this->route('stepId'),
            $this->validatedPayloadOnly(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPayloadOnly(): array
    {
        return collect($this->validated())
            ->except(self::INTERNAL_RULE_KEYS)
            ->all();
    }

    private function mergeApprovalWithinHoursFromShortKey(): void
    {
        if ($this->has('approval_within_s') && ! $this->has('approval_within_hours')) {
            $this->merge(['approval_within_hours' => $this->input('approval_within_s')]);
        }
    }

    private function mergeRouteBindingForValidation(): void
    {
        $this->merge([
            'procedure_setting_route_id'      => $this->route('procedureSettingId'),
            'procedure_setting_step_route_id' => $this->route('stepId'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function routeBindingRules(): array
    {
        return [
            'procedure_setting_route_id' => ['required', 'uuid', $this->procedureSettingExistsRule()],
            'procedure_setting_step_route_id' => ['required', 'integer', $this->stepBelongsToProcedureSettingRule()],
            'procedure_setting_id' => [
                'sometimes',
                'uuid',
                Rule::in([(string) $this->route('procedureSettingId')]),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadRules(): array
    {
        return [
            'name'         => 'sometimes|nullable|string|max:255',
            'is_accept'    => 'sometimes|boolean',
            'is_approve'   => 'sometimes|boolean',

            // When action_taker_type is "himself" or "assigned_user" only the "approve" form is permitted.
            'forms' => [
                'sometimes',
                'nullable',
                'string',
                'in:approve,accept,financial',
                Rule::when(
                    in_array($this->input('action_taker_type'), ['himself', 'assigned_user'], true),
                    ['in:approve'],
                ),
            ],

            'branch_id'      => ['sometimes', 'nullable', 'integer', Rule::exists('management_hierarchies', 'id')->where('type', 'branch')],
            'management_id' => 'sometimes|nullable|integer|exists:management_hierarchies,id',

            'is_view_only'                     => 'sometimes|boolean',
            'is_return_with_notes'             => 'sometimes|boolean',
            'requires_approval_within_period' => 'sometimes|boolean',
            'approval_within_days'            => 'sometimes|nullable|integer|min:0',
            'approval_within_hours'           => 'sometimes|nullable|integer|min:0',

            'notify_by_email'    => 'sometimes|boolean',
            'notify_by_whatsapp' => 'sometimes|boolean',
            'notify_by_sms'      => 'sometimes|boolean',
            'skipping_period'    => 'sometimes|nullable|integer|min:0',

            'escalation_management_hierarchy_id' => 'sometimes|nullable|integer|exists:management_hierarchies,id',

            'step_order' => 'sometimes|nullable|integer|min:0',

            'action_taker_type' => 'sometimes|nullable|string|in:specific_user,management_hierarchy,specific_procedures,himself,assigned_user',

            // ── Deprecated (legacy) ──────────────────────────────────────────
            // Replaced by action_taker_management_hierarchies array of objects.
            // Still accepted for backward compatibility but not required.
            'action_taker_management_hierarchy_type' => [
                'sometimes',
                'nullable',
                'string',
                'in:branch_manager,management_manager,project_manager,deputy_manager',
                'prohibited_unless:action_taker_type,management_hierarchy',
            ],

            'action_taker_alternative_management_hierarchy_type' => [
                'sometimes',
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
                'sometimes',
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
                'sometimes',
                'nullable',
                'array',
                'required_if:action_taker_type,specific_procedures',
                'prohibited_unless:action_taker_type,specific_procedures',
            ],
            'action_taker_specific_procedure_type.*' => 'string|in:branch,management,job_title,job_role',

            'action_taker_specific_procedure_id' => [
                'sometimes',
                'nullable',
                'array',
                'required_if:action_taker_type,specific_procedures',
                'prohibited_unless:action_taker_type,specific_procedures',
            ],
            'action_taker_specific_procedure_id.*' => 'string',

            'action_taker_user_ids'   => [
                'sometimes',
                'array',
                'required_if:action_taker_type,specific_user',
                new ActionTakerUserIdsUniquePerProcedureSetting(
                    (string) $this->route('procedureSettingId'),
                    (int) $this->route('stepId'),
                ),
            ],
            'action_taker_user_ids.*' => 'uuid|exists:users,id',
            'concerned_management_hierarchy_ids'  => 'sometimes|array',
            'concerned_management_hierarchy_ids.*' => 'integer|exists:management_hierarchies,id',
        ];
    }

    private function procedureSettingExistsRule(): Exists
    {
        $rule = Rule::exists('procedure_settings', 'id');
        if ($this->tenantCompanyId() !== null) {
            $rule = $rule->where('company_id', $this->tenantCompanyId());
        }

        return $rule;
    }

    private function stepBelongsToProcedureSettingRule(): Exists
    {
        $rule = Rule::exists('procedure_setting_steps', 'id')
            ->where('procedure_setting_id', (string) $this->route('procedureSettingId'));

        if ($this->tenantCompanyId() !== null) {
            $rule = $rule->where('company_id', $this->tenantCompanyId());
        }

        return $rule;
    }

    private function tenantCompanyId(): ?string
    {
        if (! tenancy()->initialized || ! tenant('id')) {
            return null;
        }

        return (string) tenant('id');
    }
}
