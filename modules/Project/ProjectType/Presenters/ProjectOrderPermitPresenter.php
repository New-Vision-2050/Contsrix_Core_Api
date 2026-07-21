<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
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
}
