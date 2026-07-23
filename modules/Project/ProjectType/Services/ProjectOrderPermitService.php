<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\UdsProjectOrderPermit;
use Illuminate\Support\Facades\Log;

class ProjectOrderPermitService
{
    public function createMany(array $data): array
    {
        $projectId = (string) Arr::get($data, 'project_id');
        $items = [];

        foreach (Arr::get($data, 'work_orders', []) as $workOrderData) {
            $name = Arr::get($workOrderData, 'name');
            $orderPermitId = Arr::get($workOrderData, 'order_permit_id');

            $exists = ProjectOrderPermit::where('name', $name)
                ->where('order_permit_id', $orderPermitId)
                ->exists();

            if ($exists) {
                throw new \Exception("أمر العمل '{$name}' مع نوع الأمر '{$orderPermitId}' موجود مسبقاً.", 422);
            }

            $item = ProjectOrderPermit::query()->create([
                'project_id' => $projectId,
                'project_management_id' => Arr::get($workOrderData, 'project_management_id'),
                'projects_district_id' => Arr::get($workOrderData, 'projects_district_id'),
                'order_permit_id' => Arr::get($workOrderData, 'order_permit_id'),
                'order_permit_department_id' => Arr::get($workOrderData, 'order_permit_department_id'),
                'contractor_id' => Arr::get($workOrderData, 'contractor_id'),
                'name' => $name,
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

            $this->autoFillFromUds($item);

            $items[] = $item;
        }

        return $items;
    }

    private function autoFillFromUds(ProjectOrderPermit $order): void
    {
        $udsRecords = UdsProjectOrderPermit::where('project_id', $order->project_id)
            ->where('name', $order->name)
            ->get();

        if ($udsRecords->isEmpty()) return;

        $orderPermit = $order->orderPermit()->first();
        if (!$orderPermit) return;

        $updates = [];

        foreach ($udsRecords as $uds) {
            $typeCode = $uds->type_code;
            $isContractor = $orderPermit->code !== null && (string)$orderPermit->code === $typeCode;
            $isConsultant = $orderPermit->type !== null && (string)$orderPermit->type === $typeCode;

            if (!$isContractor && !$isConsultant) continue;

            if ($isContractor) {
                $updates['executing_entity'] = $uds->executing_entity;
                $updates['office'] = $uds->office;
                $updates['contractor_basket'] = $uds->contractor_basket;
                $updates['contractor_last_procedure_code'] = $uds->contractor_last_procedure_code;
                $updates['contractor_last_procedure_date'] = $uds->contractor_last_procedure_date;
                $updates['contractor_column_155_entry_date'] = $uds->contractor_column_155_entry_date;
                $updates['material_balance_elec_contractor'] = $uds->material_balance_elec_contractor;
                $updates['contractor_work_order_status'] = $uds->contractor_work_order_status;
            } else {
                $updates['consultant_current_basket'] = $uds->consultant_current_basket;
                $updates['assigned_date'] = $uds->assigned_date;
                $updates['consultant_assignment_date'] = $uds->consultant_assignment_date;
                $updates['consultant_last_procedure_code'] = $uds->consultant_last_procedure_code;
                $updates['consultant_last_procedure_date'] = $uds->consultant_last_procedure_date;
                $updates['consultant_column_155_entry_date'] = $uds->consultant_column_155_entry_date;
                $updates['price'] = $uds->price;
                $updates['consultant_price'] = $uds->consultant_price;
            }
        }

        $updates = array_filter($updates, fn($v) => $v !== null);
        if (!empty($updates)) {
            $order->update($updates);
            Log::info("Auto-filled order {$order->name} from UDS table.");
        }
    }

    public function list(string $projectId, array $filters = []): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->when(Arr::get($filters, 'order_permit_department_id'), fn ($q, $deptId) =>
                $q->whereHas('orderPermit', fn ($q2) => $q2->where('order_permit_department_id', $deptId))
            )
            ->with(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function listAll(array $filters = []): Collection
    {
        $projectIds = ProjectManagement::query()->pluck('id');

        return ProjectOrderPermit::query()
            ->whereIn('project_id', $projectIds)
            ->when(Arr::get($filters, 'order_permit_department_id'), fn ($q, $deptId) =>
                $q->whereHas('orderPermit', fn ($q2) => $q2->where('order_permit_department_id', $deptId))
            )
            ->with(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function show(string $projectId, string $id): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->firstOrFail();
    }

    public function update(string $projectId, string $id, array $data): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        $newName = Arr::get($data, 'name', $orderPermit->name);
        $newOrderPermitId = Arr::get($data, 'order_permit_id', $orderPermit->order_permit_id);

        if ($newName !== $orderPermit->name || $newOrderPermitId != $orderPermit->order_permit_id) {
            $exists = ProjectOrderPermit::where('name', $newName)
                ->where('order_permit_id', $newOrderPermitId)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw new \Exception("يوجد أمر عمل بنفس الرقم '{$newName}' ونوع الأمر '{$newOrderPermitId}' بالفعل.", 422);
            }
        }

        $orderPermit->update([
            'project_management_id' => Arr::get($data, 'project_management_id', $orderPermit->project_management_id),
            'projects_district_id' => Arr::get($data, 'projects_district_id', $orderPermit->projects_district_id),
            'order_permit_id' => $newOrderPermitId,
            'order_permit_department_id' => Arr::get($data, 'order_permit_department_id', $orderPermit->order_permit_department_id),
            'contractor_id' => Arr::get($data, 'contractor_id', $orderPermit->contractor_id),
            'name' => $newName,
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

        return $orderPermit->fresh(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict']);
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
