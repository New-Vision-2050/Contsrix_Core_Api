<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Presenters;

use Modules\ProcedureSetting\Models\ProcedureSetting;
use Modules\Shared\InternalProcessType\Enums\InternalProcessForm;
use BasePackage\Shared\Presenters\AbstractPresenter;

class ProcedureSettingPresenter extends AbstractPresenter
{
    private ProcedureSetting $procedureSetting;

    public function __construct(ProcedureSetting $procedureSetting)
    {
        $this->procedureSetting = $procedureSetting;
    }

    protected function present(bool $isListing = false): array
    {
        $form = $this->procedureSetting->form
            ? InternalProcessForm::tryFrom($this->procedureSetting->form)
            : null;

        $data = [
            'id'           => $this->procedureSetting->id,
            'name'         => $this->procedureSetting->name,
            'type'         => $this->procedureSetting->type,
            'execute_type' => $this->procedureSetting->execute_type,
            'icon'         => $this->procedureSetting->icon,
            'percentage'   => $this->procedureSetting->percentage,
            'deadline_days'  => $this->procedureSetting->deadline_days,
            'deadline_hours' => $this->procedureSetting->deadline_hours,
            'sort_order'        => $this->procedureSetting->sort_order,
            'is_active'         => $this->procedureSetting->is_active,
            'parent_id'         => $this->procedureSetting->parent_id,
            'appears_before_id' => $this->procedureSetting->appears_before_id,
            'appears_after_id'  => $this->procedureSetting->appears_after_id,
            'form'              => $form ? [
                'key'        => $form->value,
                'label_ar'   => $form->labelAr(),
                'conditions' => array_map(
                    static fn ($c) => $c->toDefinition(),
                    $form->conditions(),
                ),
            ] : null,
            'conditions'        => $this->procedureSetting->conditions ?? [],
            'escalation_management_hierarchy_id' => $this->procedureSetting->escalation_management_hierarchy_id,
            'escalation_management_hierarchy'    => $this->escalationManagementHierarchyPayload(),
            'work_flow_id'       => $this->procedureSetting->work_flow_id,
            'work_flow'          => $this->workFlowPayload(),
            'is_internal_procedure' => $this->procedureSetting->isInternalProcedure(),
        ];

        if (! $isListing && $this->procedureSetting->relationLoaded('steps')) {
            $data['steps'] = $this->procedureSetting->steps->map(
                static fn ($step) => (new ProcedureSettingStepPresenter($step))->getData()
            )->all();
        }

        return $data;
    }

    private function escalationManagementHierarchyPayload(): ?array
    {
        if ($this->procedureSetting->escalation_management_hierarchy_id === null) {
            return null;
        }

        $mh = $this->procedureSetting->relationLoaded('escalationManagementHierarchy')
            ? $this->procedureSetting->escalationManagementHierarchy
            : $this->procedureSetting->escalationManagementHierarchy()->first(['id', 'name', 'type', 'company_id']);

        if ($mh === null) {
            return null;
        }

        return [
            'id'         => $mh->id,
            'name'       => $mh->name,
            'type'       => $mh->type,
            'company_id' => $mh->company_id ?? null,
        ];
    }

    private function workFlowPayload(): ?array
    {
        if ($this->procedureSetting->work_flow_id === null) {
            return null;
        }

        $workFlow = $this->procedureSetting->relationLoaded('workFlow')
            ? $this->procedureSetting->workFlow
            : $this->procedureSetting->workFlow()->first(['id', 'name']);

        if ($workFlow === null) {
            return null;
        }

        return [
            'id'         => $workFlow->id,
            'name'       => $workFlow->name,
            'company_id' => $workFlow->company_id ?? null,
        ];
    }
}
