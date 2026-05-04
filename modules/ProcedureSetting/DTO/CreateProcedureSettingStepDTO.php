<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\DTO;

class CreateProcedureSettingStepDTO
{
    /**
     * @param list<string>|null $action_taker_user_ids
     * @param list<string>|null $concerned_user_ids
     */
    public function __construct(
        public readonly string $procedure_setting_id,
        public readonly ?string $name = null,
        public readonly bool $is_accept = false,
        public readonly bool $is_approve = false,
        public readonly ?string $forms = null,
        public readonly ?int $branch_id = null,
        public readonly ?int $management_id = null,
        public readonly bool $is_view_only = false,
        public readonly bool $is_return_with_notes = false,
        public readonly bool $requires_approval_within_period = false,
        public readonly ?int $approval_within_days = null,
        public readonly ?int $approval_within_hours = null,
        public readonly bool $notify_by_email = false,
        public readonly bool $notify_by_whatsapp = false,
        public readonly ?string $escalation_user_id = null,
        public readonly ?int $step_order = null,
        public readonly ?array $action_taker_user_ids = null,
        public readonly ?array $concerned_user_ids = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'procedure_setting_id'             => $this->procedure_setting_id,
            'name'                             => $this->name,
            'is_accept'                        => $this->is_accept,
            'is_approve'                       => $this->is_approve,
            'forms'                            => $this->forms,
            'branch_id'                        => $this->branch_id,
            'management_id'                    => $this->management_id,
            'is_view_only'                     => $this->is_view_only,
            'is_return_with_notes'             => $this->is_return_with_notes,
            'requires_approval_within_period' => $this->requires_approval_within_period,
            'approval_within_days'            => $this->approval_within_days,
            'approval_within_hours'           => $this->approval_within_hours,
            'notify_by_email'                  => $this->notify_by_email,
            'notify_by_whatsapp'               => $this->notify_by_whatsapp,
            'escalation_user_id'               => $this->escalation_user_id,
            'step_order'                       => $this->step_order,
        ];

        if ($this->action_taker_user_ids !== null) {
            $data['action_taker_user_ids'] = $this->action_taker_user_ids;
        }
        if ($this->concerned_user_ids !== null) {
            $data['concerned_user_ids'] = $this->concerned_user_ids;
        }

        return $data;
    }
}
