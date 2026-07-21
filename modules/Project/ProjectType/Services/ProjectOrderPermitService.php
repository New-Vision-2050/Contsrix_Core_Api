<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class ProjectOrderPermitService
{
 public function createMany(array $data): array
    {
        $projectId = (string) Arr::get($data, 'project_id');
        $items = [];

        foreach (Arr::get($data, 'work_orders', []) as $workOrderData) {
            $items[] = ProjectOrderPermit::query()->create([
                'project_id' => $projectId,
                'project_management_id' => Arr::get($workOrderData, 'project_management_id'),
                'projects_district_id' => Arr::get($workOrderData, 'projects_district_id'),
                'order_permit_id' => Arr::get($workOrderData, 'order_permit_id'),
                'order_permit_department_id' => Arr::get($workOrderData, 'order_permit_department_id'),
                'contractor_id' => Arr::get($workOrderData, 'contractor_id'),
                'name' => Arr::get($workOrderData, 'name'),
                'type' => Arr::get($workOrderData, 'type'),
                'assigned_date' => Arr::get($workOrderData, 'assigned_date'),
                'state_id' => Arr::get($workOrderData, 'state_id'),
                'lat' => Arr::get($workOrderData, 'lat'),
                'long' => Arr::get($workOrderData, 'long'),
                'price' => Arr::get($workOrderData, 'price'),
                'executing_entity' => Arr::get($workOrderData, 'executing_entity'),
                'office' => Arr::get($workOrderData, 'office'),
                'consultant_current_basket' => Arr::get($workOrderData, 'consultant_current_basket'),
                'consultant_assignment_date' => Arr::get($workOrderData, 'consultant_assignment_date'),
                'consultant_last_procedure_code' => Arr::get($workOrderData, 'consultant_last_procedure_code'),
                'consultant_last_procedure_date' => Arr::get($workOrderData, 'consultant_last_procedure_date'),
                'consultant_column_155_entry_date' => Arr::get($workOrderData, 'consultant_column_155_entry_date'),
                'contractor_last_procedure_code' => Arr::get($workOrderData, 'contractor_last_procedure_code'),
                'contractor_last_procedure_date' => Arr::get($workOrderData, 'contractor_last_procedure_date'),
                'contractor_column_155_entry_date' => Arr::get($workOrderData, 'contractor_column_155_entry_date'),
                'material_balance_elec_contractor' => Arr::get($workOrderData, 'material_balance_elec_contractor'),
                'contractor_work_order_status' => Arr::get($workOrderData, 'contractor_work_order_status'),
                'contractor_basket' => Arr::get($workOrderData, 'contractor_basket'),
                'consultant_price' => Arr::get($workOrderData, 'consultant_price'),
            ]);
        }

        return $items;
    }

    public function list(string $projectId): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->with(['orderPermit.orderPermitType', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listAll(): Collection
    {
        $projectIds = ProjectManagement::query()->pluck('id');

        return ProjectOrderPermit::query()
            ->whereIn('project_id', $projectIds)
            ->with(['orderPermit.orderPermitType', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function show(string $projectId, string $id): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit.orderPermitType', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->firstOrFail();
    }


    public function update(string $projectId, string $id, array $data): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        $orderPermit->update([
            'project_management_id' => Arr::get($data, 'project_management_id', $orderPermit->project_management_id),
            'projects_district_id' => Arr::get($data, 'projects_district_id', $orderPermit->projects_district_id),
            'order_permit_id' => Arr::get($data, 'order_permit_id', $orderPermit->order_permit_id),
            'order_permit_department_id' => Arr::get($data, 'order_permit_department_id', $orderPermit->order_permit_department_id),
            'contractor_id' => Arr::get($data, 'contractor_id', $orderPermit->contractor_id),
            'name' => Arr::get($data, 'name', $orderPermit->name),
            'type' => Arr::get($data, 'type', $orderPermit->type),
            'assigned_date' => Arr::get($data, 'assigned_date', $orderPermit->assigned_date),
            'state_id' => Arr::get($data, 'state_id', $orderPermit->state_id),
            'lat' => Arr::get($data, 'lat', $orderPermit->lat),
            'long' => Arr::get($data, 'long', $orderPermit->long),
            'price' => Arr::get($data, 'price', $orderPermit->price),
            'executing_entity' => Arr::get($data, 'executing_entity', $orderPermit->executing_entity),
            'office' => Arr::get($data, 'office', $orderPermit->office),
            'consultant_current_basket' => Arr::get($data, 'consultant_current_basket', $orderPermit->consultant_current_basket),
            'consultant_assignment_date' => Arr::get($data, 'consultant_assignment_date', $orderPermit->consultant_assignment_date),
            'consultant_last_procedure_code' => Arr::get($data, 'consultant_last_procedure_code', $orderPermit->consultant_last_procedure_code),
            'consultant_last_procedure_date' => Arr::get($data, 'consultant_last_procedure_date', $orderPermit->consultant_last_procedure_date),
            'consultant_column_155_entry_date' => Arr::get($data, 'consultant_column_155_entry_date', $orderPermit->consultant_column_155_entry_date),
            'contractor_last_procedure_code' => Arr::get($data, 'contractor_last_procedure_code', $orderPermit->contractor_last_procedure_code),
            'contractor_last_procedure_date' => Arr::get($data, 'contractor_last_procedure_date', $orderPermit->contractor_last_procedure_date),
            'contractor_column_155_entry_date' => Arr::get($data, 'contractor_column_155_entry_date', $orderPermit->contractor_column_155_entry_date),
            'material_balance_elec_contractor' => Arr::get($data, 'material_balance_elec_contractor', $orderPermit->material_balance_elec_contractor),
            'contractor_work_order_status' => Arr::get($data, 'contractor_work_order_status', $orderPermit->contractor_work_order_status),
            'contractor_basket' => Arr::get($data, 'contractor_basket', $orderPermit->contractor_basket),
            'consultant_price' => Arr::get($data, 'consultant_price', $orderPermit->consultant_price),
        ]);


        return $orderPermit->fresh(['orderPermit.orderPermitType', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict']);
    }


    public function delete(string $projectId, string $id): bool
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        return (bool) $orderPermit->delete();
    }
}
