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
            'notify_by_sms'        => (bool) $this->step->notify_by_sms,
            'notify_by_push'       => (bool) $this->step->notify_by_push,
            'skipping_period'      => $this->step->skipping_period,
            'escalation_management_hierarchy_id' => $this->step->escalation_management_hierarchy_id,
            'escalation_management_hierarchy'    => $this->escalationManagementHierarchyPayload(),
            'action_taker_type'                  => $this->step->action_taker_type?->value,
            'action_taker_type_label'            => $this->resolveActionTakerTypeLabel(),
            'action_taker_management_hierarchy_type' => $this->step->action_taker_management_hierarchy_type?->value,
            'action_taker_management_hierarchy_type_label' => $this->resolveHierarchyTypeLabel(),

            // Array of fallback types e.g. ["branch_manager","deputy_manager"]
            'action_taker_alternative_management_hierarchy_type' => $this->step->action_taker_alternative_management_hierarchy_type ?? [],
            'action_taker_alternative_management_hierarchy_type_labels' => $this->resolveAlternativeHierarchyTypeLabels(),

            // New canonical format: array of {action_taker_management_hierarchy_type, is_Deputy_Director} objects.
            'action_taker_management_hierarchies' => $this->resolveActionTakerManagementHierarchies(),

            // Parallel arrays forming [{type,id}] pairs
            'action_taker_specific_procedure_type' => $this->step->action_taker_specific_procedure_type ?? [],
            'action_taker_specific_procedure_id'   => $this->step->action_taker_specific_procedure_id ?? [],

            // Convenience combined format for frontend: [{type,id}]
            'action_taker_specific_procedures' => $this->resolveSpecificProceduresCombined(),

            'action_taker_hierarchy'             => $this->resolveActionTakerHierarchyPayload(),
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

    private function resolveActionTakerTypeLabel(): ?string
    {
        return match ($this->step->action_taker_type?->value) {
            'management_hierarchy' => 'Management Hierarchy',
            'specific_procedures'  => 'Specific Procedures',
            'himself'              => 'Himself',
            'assigned_user'        => 'Assigned User',
            default                => 'Specific User',
        };
    }

    private function resolveHierarchyTypeLabel(): ?string
    {
        return match ($this->step->action_taker_management_hierarchy_type?->value) {
            'branch_manager'     => 'Branch Manager',
            'management_manager' => 'Management Manager',
            'project_manager'    => 'Project Manager',
            'deputy_manager'     => 'Deputy Manager',
            default              => null,
        };
    }

    /**
     * Returns an array of labels matching the alternative types array.
     * e.g. ["Branch Manager", "Deputy Manager"]
     *
     * @return list<string>
     */
    private function resolveAlternativeHierarchyTypeLabels(): array
    {
        $types = (array) ($this->step->action_taker_alternative_management_hierarchy_type ?? []);

        return array_values(array_map(
            fn (string $type) => match ($type) {
                'branch_manager'     => 'Branch Manager',
                'management_manager' => 'Management Manager',
                'deputy_manager'     => 'Deputy Manager',
                default              => $type,
            },
            $types,
        ));
    }

    /**
     * Combines the parallel type/id arrays into a single array of objects for convenience.
     * Returns [{type: "branch", id: "5"}, ...]
     *
     * @return list<array{type: string, id: string}>
     */
    private function resolveSpecificProceduresCombined(): array
    {
        $types = (array) ($this->step->action_taker_specific_procedure_type ?? []);
        $ids   = (array) ($this->step->action_taker_specific_procedure_id   ?? []);

        $combined = [];
        foreach ($types as $index => $type) {
            $combined[] = [
                'type' => (string) $type,
                'id'   => (string) ($ids[$index] ?? ''),
            ];
        }

        return $combined;
    }

    private function resolveActionTakerHierarchyPayload(): ?array
    {
        if ($this->step->action_taker_type?->value !== 'management_hierarchy') {
            return null;
        }

        return [
            'type'  => $this->step->action_taker_management_hierarchy_type?->value,
            'label' => $this->resolveHierarchyTypeLabel(),
        ];
    }

    /**
     * Returns the action_taker_management_hierarchies array from the new column.
     * If the new column is empty, builds it from legacy fields for backward compatibility.
     *
     * @return list<array{action_taker_management_hierarchy_type: string, is_Deputy_Director: bool}>
     */
    private function resolveActionTakerManagementHierarchies(): array
    {
        $hierarchies = $this->step->action_taker_management_hierarchies;

        if (!empty($hierarchies)) {
            return array_map(static function (array $item): array {
                return [
                    'action_taker_management_hierarchy_type' => $item['action_taker_management_hierarchy_type'] ?? '',
                    'is_Deputy_Director'                     => (bool) ($item['is_Deputy_Director'] ?? false),
                ];
            }, $hierarchies);
        }

        // Backward-compatible build from legacy fields.
        $result = [];

        $primaryType = $this->step->action_taker_management_hierarchy_type?->value;
        if ($primaryType !== null && $primaryType !== '') {
            if ($primaryType === 'deputy_manager') {
                $result[] = [
                    'action_taker_management_hierarchy_type' => '',
                    'is_Deputy_Director'                     => true,
                ];
            } else {
                $result[] = [
                    'action_taker_management_hierarchy_type' => $primaryType,
                    'is_Deputy_Director'                     => false,
                ];
            }
        }

        $alternatives = (array) ($this->step->action_taker_alternative_management_hierarchy_type ?? []);
        foreach ($alternatives as $altType) {
            if ($altType === 'deputy_manager') {
                $result[] = [
                    'action_taker_management_hierarchy_type' => '',
                    'is_Deputy_Director'                     => true,
                ];
            } else {
                $result[] = [
                    'action_taker_management_hierarchy_type' => (string) $altType,
                    'is_Deputy_Director'                     => false,
                ];
            }
        }

        return $result;
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
