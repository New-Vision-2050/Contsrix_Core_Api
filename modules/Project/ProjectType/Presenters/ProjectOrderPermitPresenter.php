<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Carbon\Carbon;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

class ProjectOrderPermitPresenter extends AbstractPresenter
{
    public function __construct(private readonly ProjectOrderPermit $model)
    {
    }

    protected function present(bool $isListing = false): array
    {
        return [
            'id' => $this->model->id,
            'project_id' => $this->model->project_id,
            'project_management_id' => $this->model->project_management_id,
            'project_management_name' => $this->model->projectManagement?->name,
            'projects_district_id' => $this->model->projects_district_id,
            'projects_district_name' => $this->model->projectDistrict?->name,
            'order_permit_id' => $this->model->order_permit_id,
            'order_permit' => $this->model->orderPermit ? [
                'id' => $this->model->orderPermit->id,
                'code' => $this->model->orderPermit->code,
                'description' => $this->model->orderPermit->description,
                'type' => $this->model->orderPermit->type,
                'uds_period' => $this->model->orderPermit->uds_period,
                'order_permit_department_id' => $this->model->orderPermit->order_permit_department_id,
                'department_name' => $this->model->orderPermit->department?->name,
                'order_permit_type_id' => $this->model->orderPermit->order_permit_type_id,
                'order_permit_type_name' => $this->model->orderPermit->orderPermitType?->name,
            ] : null,
            'order_permit_department_id' => $this->model->order_permit_department_id,
            'department_name' => $this->model->department?->name,
            'contractor_id' => $this->model->contractor_id,
            'contractor_name' => $this->model->contractor?->name,
            'name' => $this->model->name,
            'type' => $this->model->type,
            'assigned_date' => $this->model->assigned_date?->toDateString(),
            'state_id' => $this->model->state_id,
            'state_name' => $this->model->state?->name,
            'project_completion_phase_id' => $this->model->project_completion_phase_id,
            'project_completion_phase_name' => $this->model->projectCompletionPhase?->name,
            'project_phase_status_id' => $this->model->project_phase_status_id,
            'project_phase_status_name' => $this->model->projectPhaseStatus?->name,
            'connection_completion_phase_id' => $this->model->connection_completion_phase_id,
            'connection_completion_phase_name' => $this->model->connectionCompletionPhase?->name,
            'connection_phase_status_id' => $this->model->connection_phase_status_id,
            'connection_phase_status_name' => $this->model->connectionPhaseStatus?->name,
            'completion_phase_id' => $this->getCompletionPhaseId(),
            'completion_phase_name' => $this->getCompletionPhaseName(),
            'phase_status_id' => $this->getPhaseStatusId(),
            'phase_status_name' => $this->getPhaseStatusName(),
            'start_permit_date' => $this->model->start_permit_date?->toDateString(),
            'end_permit_date' => $this->model->end_permit_date?->toDateString(),
            'note_from_permit_to_departments' => $this->model->note_from_permit_to_departments,
            'is_taked_action' => $this->model->is_taked_action,
            'count_of_days_from_assigned_date' => $this->getCountOfDaysFromAssignedDate(),
            'permit_status' => $this->getPermitStatus(),
            'lat' => $this->model->lat,
            'import_log' => $this->model->import_log,
            'long' => $this->model->long,
            'price' => $this->model->price,
            'executing_entity' => $this->model->executing_entity,
            'office' => $this->model->office,
            'consultant_current_basket' => $this->model->consultant_current_basket,
            'consultant_assignment_date' => $this->model->consultant_assignment_date?->toDateString(),
            'consultant_last_procedure_code' => $this->model->consultant_last_procedure_code,
            'consultant_last_procedure_date' => $this->model->consultant_last_procedure_date?->toDateString(),
            'consultant_column_155_entry_date' => $this->model->consultant_column_155_entry_date?->toDateString(),
            'contractor_last_procedure_code' => $this->model->contractor_last_procedure_code,
            'contractor_last_procedure_date' => $this->model->contractor_last_procedure_date?->toDateString(),
            'contractor_column_155_entry_date' => $this->model->contractor_column_155_entry_date?->toDateString(),
            'material_balance_elec_contractor' => $this->model->material_balance_elec_contractor,
            'contractor_work_order_status' => $this->model->contractor_work_order_status,
            'contractor_basket' => $this->model->contractor_basket,
            'consultant_price' => $this->model->consultant_price,
            'last_row_update_at' => $this->model->last_row_update_at?->toDateTimeString(),
            'created_at' => $this->model->created_at?->toDateTimeString(),
            'updated_at' => $this->model->updated_at?->toDateTimeString(),
        ];
    }

    private function getCountOfDaysFromAssignedDate(): ?int
    {
        if (! $this->model->assigned_date) {
            return null;
        }

        return (int) $this->model->assigned_date->startOfDay()->diffInDays(Carbon::today()->startOfDay(), false);
    }

    private function isProjectDepartment(): bool
    {
        return $this->model->department?->name === 'مشاريع';
    }

    private function isConnectionDepartment(): bool
    {
        return $this->model->department?->name === 'توصيلات';
    }

    private function getCompletionPhaseId(): ?int
    {
        if ($this->isProjectDepartment()) {
            return $this->model->project_completion_phase_id;
        }

        if ($this->isConnectionDepartment()) {
            return $this->model->connection_completion_phase_id;
        }

        return null;
    }

    private function getCompletionPhaseName(): ?string
    {
        if ($this->isProjectDepartment()) {
            return $this->model->projectCompletionPhase?->name;
        }

        if ($this->isConnectionDepartment()) {
            return $this->model->connectionCompletionPhase?->name;
        }

        return null;
    }

    private function getPhaseStatusId(): ?int
    {
        if ($this->isProjectDepartment()) {
            return $this->model->project_phase_status_id;
        }

        if ($this->isConnectionDepartment()) {
            return $this->model->connection_phase_status_id;
        }

        return null;
    }

    private function getPhaseStatusName(): ?string
    {
        if ($this->isProjectDepartment()) {
            return $this->model->projectPhaseStatus?->name;
        }

        if ($this->isConnectionDepartment()) {
            return $this->model->connectionPhaseStatus?->name;
        }

        return null;
    }

    private function getPermitStatus(): string
    {
        $departmentName = $this->model->department?->name;
        $days = $this->getCountOfDaysFromAssignedDate();

        if ($departmentName === 'مشاريع') {
            $phaseName = $this->model->projectCompletionPhase?->name;
            $statusName = $this->model->projectPhaseStatus?->name;
        } elseif ($departmentName === 'توصيلات') {
            $phaseName = $this->model->connectionCompletionPhase?->name;
            $statusName = $this->model->connectionPhaseStatus?->name;
        } else {
            $phaseName = null;
            $statusName = null;
        }

        if ($phaseName === 'التصاريح' && in_array($statusName, ['لا يحتاج', 'تم اصدار التصريح'], true)) {
            return 'غير متاخر';
        }

        if ($days !== null && $days >= 6) {
            return 'متاخر جدا';
        }

        if ($days !== null && $days >= 3) {
            return 'متاخر';
        }

        return 'غير متاخر';
    }
}
