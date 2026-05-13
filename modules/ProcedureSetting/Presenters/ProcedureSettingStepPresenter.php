<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Presenters;

use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProcedureSettingStepPresenter extends AbstractPresenter
{
    public function __construct(
        private ProcedureSettingStep $step,
    ) {
    }

    protected function present(bool $isListing = false): ?array
    {
        $data = [
            'id'                   => $this->step->id,
            'procedure_setting_id' => $this->step->procedure_setting_id,
            'company_id'           => $this->step->company_id,
            'name'                 => $this->step->name,
            'branch_id'            => $this->step->branch_id,
            'management_id'        => $this->step->management_id,
            'is_accept'            => $this->step->is_accept,
            'is_approve'           => $this->step->is_approve,
            'forms'                => $this->step->forms,
            'is_view_only'         => (bool) $this->step->is_view_only,
            'is_return_with_notes' => (bool) $this->step->is_return_with_notes,
            'requires_approval_within_period' => (bool) $this->step->requires_approval_within_period,
            'approval_within_days'  => $this->step->approval_within_days,
            'approval_within_hours' => $this->step->approval_within_hours,
            'notify_by_email'      => (bool) $this->step->notify_by_email,
            'notify_by_whatsapp'   => (bool) $this->step->notify_by_whatsapp,
            'escalation_management_hierarchy_id' => $this->step->escalation_management_hierarchy_id,
            'escalation_management_hierarchy'    => $this->escalationManagementHierarchyPayload(),
        ];

        if ($this->step->relationLoaded('branch') && $this->step->branch) {
            $data['branch'] = [
                'id'   => $this->step->branch->id,
                'name' => $this->step->branch->name,
                'type' => $this->step->branch->type,
            ];
        }

        if ($this->step->relationLoaded('management') && $this->step->management) {
            $data['management'] = [
                'id'   => $this->step->management->id,
                'name' => $this->step->management->name,
                'type' => $this->step->management->type,
            ];
        }

        if ($this->step->relationLoaded('actionTakers')) {
            $data['action_takers'] = $this->step->actionTakers->map(function ($row) {
                $out = [
                    'id'      => $row->id,
                    'user_id' => $row->user_id,
                ];
                if ($row->relationLoaded('user') && $row->user) {
                    $out['user'] = $this->userMini($row->user);
                }

                return $out;
            })->values()->all();
        }

        if ($this->step->relationLoaded('concernedManagementHierarchies')) {
            $data['concerned_management_hierarchies'] = $this->step->concernedManagementHierarchies->map(function ($row) {
                $out = [
                    'id'                       => $row->id,
                    'management_hierarchy_id'  => $row->management_hierarchy_id,
                ];
                if ($row->relationLoaded('managementHierarchy') && $row->managementHierarchy) {
                    $out['management_hierarchy'] = [
                        'id'   => $row->managementHierarchy->id,
                        'name' => $row->managementHierarchy->name,
                        'type' => $row->managementHierarchy->type,
                    ];
                }

                return $out;
            })->values()->all();
        }

        return $data;
    }

    private function escalationManagementHierarchyPayload(): ?array
    {
        if ($this->step->escalation_management_hierarchy_id === null) {
            return null;
        }

        $mh = $this->step->relationLoaded('escalationManagementHierarchy')
            ? $this->step->escalationManagementHierarchy
            : $this->step->escalationManagementHierarchy()->first(['id', 'name', 'type']);

        if ($mh === null) {
            return null;
        }

        return [
            'id'   => $mh->id,
            'name' => $mh->name,
            'type' => $mh->type,
        ];
    }

    private function userMini($user): array
    {
        return [
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email ?? null,
        ];
    }
}
