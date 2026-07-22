<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use Modules\Project\ProjectType\Models\UdsExcelSheet;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
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

            $this->autoFillFromUds($item);

            $items[] = $item;
        }

        return $items;
    }


private function autoFillFromUds(ProjectOrderPermit $order): void
{
    try {
        $udsSheet = UdsExcelSheet::where('project_id', $order->project_id)->first();
        if (!$udsSheet) return;

        $media = $udsSheet->getFirstMedia('uds_sheets');
        if (!$media) return;

        $fullPath = $media->getPath();
        if (!file_exists($fullPath)) return;

        $rows = Excel::toArray([], $fullPath)[0] ?? [];
        if (empty($rows)) return;

        $matchedRows = [];
        foreach ($rows as $row) {
            if (trim((string)($row[34] ?? '')) === $order->name) {
                $matchedRows[] = $row;
            }
        }

        if (empty($matchedRows)) return;

        $orderPermit = $order->orderPermit()->first();
        if (!$orderPermit) return;

        $value = function (array $row, int $index): ?string {
            $val = trim((string)($row[$index] ?? ''));
            return $val !== '' ? $val : null;
        };
        $parseDate = function (array $row, int $index) use ($value): ?string {
            $val = $value($row, $index);
            if ($val === null) return null;
            try { return Carbon::parse($val)->format('Y-m-d'); } catch (\Exception $e) { return null; }
        };
        $parseFloat = function (array $row, int $index) use ($value): ?float {
            $val = $value($row, $index);
            return $val !== null ? (float) $val : null;
        };

        $updates = [];

        foreach ($matchedRows as $matchedRow) {
            $typeCode = trim((string)($matchedRow[35] ?? ''));
            if ($typeCode === '') continue;

            $isContractor = $orderPermit->code !== null && (string)$orderPermit->code === $typeCode;
            $isConsultant = $orderPermit->type !== null && (string)$orderPermit->type === $typeCode;

            if (!$isContractor && !$isConsultant) continue;

            if ($isContractor) {
                $updates['executing_entity'] = $value($matchedRow, 27);
                $updates['office'] = $value($matchedRow, 37);
                $updates['contractor_basket'] = $value($matchedRow, 16);
                $updates['contractor_last_procedure_code'] = $value($matchedRow, 30);
                $updates['contractor_last_procedure_date'] = $parseDate($matchedRow, 28);
                $updates['contractor_column_155_entry_date'] = $parseDate($matchedRow, 24);
                $updates['material_balance_elec_contractor'] = $value($matchedRow, 13);
                $updates['contractor_work_order_status'] = $value($matchedRow, 6);
            } else {
                $updates['consultant_current_basket'] = $value($matchedRow, 16);
                $updates['assigned_date'] = $parseDate($matchedRow, 25);
                $updates['consultant_assignment_date'] = $parseDate($matchedRow, 25);
                $updates['consultant_last_procedure_code'] = $value($matchedRow, 30);
                $updates['consultant_last_procedure_date'] = $parseDate($matchedRow, 28);
                $updates['consultant_column_155_entry_date'] = $parseDate($matchedRow, 24);
                $updates['price'] = $parseFloat($matchedRow, 12);
                $updates['consultant_price'] = $parseFloat($matchedRow, 12);
            }
        }

        $updates = array_filter($updates, fn($v) => $v !== null);

        if (!empty($updates)) {
            $order->update($updates);
            Log::info("Auto-filled order {$order->name} from UDS Excel.", ['fields' => array_keys($updates)]);
        }
    } catch (\Exception $e) {
        Log::error("Auto-fill failed for order {$order->name}: " . $e->getMessage());
    }
}

    public function list(string $projectId): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->with(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function listByDepartment(string $projectId, $departmentId): Collection
    {

        return ProjectOrderPermit::query()

            ->whereHas('orderPermit', function ($query) use ($departmentId) {
                $query->where('order_permit_department_id', $departmentId);
            })
            ->with(['orderPermit', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
    public function listAll(): Collection
    {
        $projectIds = ProjectManagement::query()->pluck('id');

        return ProjectOrderPermit::query()
            ->whereIn('project_id', $projectIds)
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

        // التحقق من التفرد قبل التحديث
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
