<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Collection;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectOrderPermitNoteLog;
use Modules\Project\ProjectType\Models\ProjectOrderPermitUds;

class ProjectOrderPermitService
{
    public function __construct(
        private readonly ConstructionArchiveFolderService $constructionArchiveFolders,
    ) {}

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
                'project_completion_phase_id' => Arr::get($workOrderData, 'project_completion_phase_id'),
                'project_phase_status_id' => Arr::get($workOrderData, 'project_phase_status_id'),
                'connection_completion_phase_id' => Arr::get($workOrderData, 'connection_completion_phase_id'),
                'connection_phase_status_id' => Arr::get($workOrderData, 'connection_phase_status_id'),
                'start_permit_date' => Arr::get($workOrderData, 'start_permit_date'),
                'end_permit_date' => Arr::get($workOrderData, 'end_permit_date'),
                'note_from_permit_to_departments' => Arr::get($workOrderData, 'note_from_permit_to_departments'),
                'note_from_departments_to_permit' => Arr::get($workOrderData, 'note_from_departments_to_permit'),
                'is_taked_action' => Arr::get($workOrderData, 'is_taked_action', false),
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
                'employee_id' => Arr::get($workOrderData, 'employee_id'),
                'target_drilling' => Arr::get($workOrderData, 'target_drilling'),
                'achieved_drilling' => Arr::get($workOrderData, 'achieved_drilling'),
                'target_extention' => Arr::get($workOrderData, 'target_extention'),
                'achieved_extention' => Arr::get($workOrderData, 'achieved_extention'),
                'description_details' => Arr::get($workOrderData, 'description_details'),
                'consultant_statement' => Arr::get($workOrderData, 'consultant_statement'),
                'last_date_consultant_statement' => Arr::get($workOrderData, 'last_date_consultant_statement'),
                'consultnat_statement_status' => Arr::get($workOrderData, 'consultnat_statement_status'),
            ]);

            // $this->autoFillFromUds($item);

            $this->createNoteLog($item, Arr::get($workOrderData, 'note_from_permit_to_departments'), ProjectOrderPermitNoteLog::TYPE_PERMIT_TO_DEPARTMENTS);
            $this->createNoteLog($item, Arr::get($workOrderData, 'note_from_departments_to_permit'), ProjectOrderPermitNoteLog::TYPE_DEPARTMENTS_TO_PERMIT);

            $this->constructionArchiveFolders->ensureWorkOrderFolder($item);

            $items[] = $item;
        }

        return $items;
    }


    public function updateWorkOrdersFromUds(string $projectId): int
    {
        $udsRecords = ProjectOrderPermitUds::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->get();

        if ($udsRecords->isEmpty()) {
            return 0;
        }

        $workOrderNames = $udsRecords->pluck('name')->filter()->unique()->values()->all();

        $existingOrders = ProjectOrderPermit::where('project_id', $projectId)
            ->whereIn('name', $workOrderNames)
            ->with(['orderPermit', 'contractor', 'department'])
            ->get()
            ->groupBy('name');

        $companyId = $udsRecords->first()->company_id;

        $contractorNames = $udsRecords->pluck('contractor_name')->filter()->unique()->values()->all();
        $contractorsByName = empty($contractorNames)
            ? collect()
            : ProjectContractor::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->whereIn('name', $contractorNames)
                ->get()
                ->keyBy('name');

        $departmentNames = $udsRecords->pluck('office')->filter()->unique()->values()->all();
        $departmentsByName = empty($departmentNames)
            ? collect()
            : OrderPermitDepartment::whereIn('name', $departmentNames)->get()->keyBy('name');

        $updates = [];
        $logMessages = [];

        foreach ($udsRecords as $uds) {
            $typeCode = $uds->type_code !== null ? trim((string) $uds->type_code) : '';
            if ($uds->name === null || $typeCode === '') {
                continue;
            }

            $candidates = $existingOrders->get($uds->name);
            if ($candidates === null || $candidates->isEmpty()) {
                continue;
            }

            foreach ($candidates as $order) {
                $orderPermit = $order->orderPermit;
                if (!$orderPermit) {
                    continue;
                }

                $isContractor =
                    $orderPermit->code !== null
                    && (string) $orderPermit->code === $typeCode;

                $isConsultant =
                    $orderPermit->type !== null
                    && (string) $orderPermit->type === $typeCode;

                if (!$isContractor && !$isConsultant) {
                    continue;
                }

                $orderId = $order->id;
                if (!isset($updates[$orderId])) {
                    $updates[$orderId] = [];
                }

                if ($isContractor) {
                    $akValue = $uds->contractor_name !== null ? trim((string) $uds->contractor_name) : null;
                    $akValue = $akValue !== '' ? $akValue : null;
                    $currentContractor = $order->contractor;

                    if ($akValue !== null) {
                        if ($currentContractor) {
                            $currentName = $currentContractor->name;
                            if ($currentName !== $akValue) {
                                $newContractor = $contractorsByName->get($akValue);
                                if ($newContractor) {
                                    $updates[$orderId]['contractor_id'] = $newContractor->id;
                                    $updates[$orderId]['import_log'] = null;
                                } else {
                                    $msg = '[' . Carbon::now()->toDateTimeString() . "] Contractor name '{$akValue}' not found; kept '{$currentName}'.";
                                    Log::warning($msg);
                                    $logMessages[$orderId] = [$msg];
                                }
                            }
                        } else {
                            $newContractor = $contractorsByName->get($akValue);
                            if ($newContractor) {
                                $updates[$orderId]['contractor_id'] = $newContractor->id;
                                $updates[$orderId]['import_log'] = null;
                            } else {
                                $msg = '[' . Carbon::now()->toDateTimeString() . "] No contractor with name '{$akValue}' found.";
                                Log::warning($msg);
                                $logMessages[$orderId] = [$msg];
                            }
                        }
                    }
                }

                $departmentName = $uds->office !== null ? trim((string) $uds->office) : null;
                $departmentName = $departmentName !== '' ? $departmentName : null;

                if ($departmentName !== null) {
                    $currentDepartment = $order->department;
                    $currentDeptName = $currentDepartment?->name;

                    if ($currentDeptName !== $departmentName) {
                        $newDepartment = $departmentsByName->get($departmentName);
                        if ($newDepartment) {
                            $updates[$orderId]['order_permit_department_id'] = $newDepartment->id;
                            if (!array_key_exists('import_log', $updates[$orderId]) || $updates[$orderId]['import_log'] !== null) {
                                $updates[$orderId]['import_log'] = null;
                            }
                        } else {
                            $msg = '[' . Carbon::now()->toDateTimeString() . "] Department '{$departmentName}' not found; kept '{$currentDeptName}'.";
                            Log::warning($msg);
                            $logMessages[$orderId][] = $msg;
                        }
                    }
                }

                if ($isContractor) {
                    $updates[$orderId]['executing_entity'] = $uds->executing_entity;
                    $updates[$orderId]['office'] = $uds->office;
                    $updates[$orderId]['contractor_basket'] = $uds->contractor_basket;
                    $updates[$orderId]['contractor_last_procedure_code'] = $uds->contractor_last_procedure_code;
                    $updates[$orderId]['contractor_last_procedure_date'] = $this->formatUdsDate($uds->contractor_last_procedure_date);
                    $updates[$orderId]['contractor_column_155_entry_date'] = $this->formatUdsDate($uds->contractor_column_155_entry_date);
                    $updates[$orderId]['material_balance_elec_contractor'] = $uds->material_balance_elec_contractor;
                    $updates[$orderId]['contractor_work_order_status'] = $uds->contractor_work_order_status;
                } else {
                    $updates[$orderId]['consultant_current_basket'] = $uds->consultant_current_basket;
                    $updates[$orderId]['assigned_date'] = $this->formatUdsDate($uds->assigned_date);
                    $updates[$orderId]['consultant_assignment_date'] = $this->formatUdsDate($uds->consultant_assignment_date);
                    $updates[$orderId]['consultant_last_procedure_code'] = $uds->consultant_last_procedure_code;
                    $updates[$orderId]['consultant_last_procedure_date'] = $this->formatUdsDate($uds->consultant_last_procedure_date);
                    $updates[$orderId]['consultant_column_155_entry_date'] = $this->formatUdsDate($uds->consultant_column_155_entry_date);
                    $updates[$orderId]['price'] = $uds->price;
                    $updates[$orderId]['consultant_price'] = $uds->consultant_price;
                }
            }
        }

        $updatedCount = 0;
        $now = Carbon::now();

        foreach ($updates as $orderId => $fields) {
            if (empty($fields)) {
                continue;
            }

            $fields['last_row_update_at'] = $now;

            if (isset($logMessages[$orderId]) && !array_key_exists('import_log', $fields)) {
                $fields['import_log'] = implode("\n", $logMessages[$orderId]);
            }

            ProjectOrderPermit::where('id', $orderId)->update($fields);
            $updatedCount++;
        }

        Log::info('UDS work-order sync completed', [
            'project_id' => $projectId,
            'updated' => $updatedCount,
            'uds_rows' => $udsRecords->count(),
        ]);

        return $updatedCount;
    }

    public function updateFromUds(string $projectId, string $name, int|string $orderPermitId): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $order = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('name', $name)
            ->where('order_permit_id', $orderPermitId)
            ->with(['orderPermit', 'contractor', 'department'])
            ->firstOrFail();

        $this->autoFillFromUds($order);

        return $order->fresh([
            'orderPermit.department',
            'department',
            'contractor',
            'state',
            'projectManagement',
            'projectDistrict',
            'projectCompletionPhase',
            'projectPhaseStatus',
            'connectionCompletionPhase',
            'connectionPhaseStatus',
            'employee',
            'noteLogs.user',
        ]);
    }

    public function autoFillFromUds(ProjectOrderPermit $order): void
    {
        try {
            $udsRecords = ProjectOrderPermitUds::withoutGlobalScopes()
                ->where('project_id', $order->project_id)
                ->where('name', $order->name)
                ->get();

            if ($udsRecords->isEmpty()) {
                return;
            }

            if (!$order->relationLoaded('orderPermit')) {
                $order->load('orderPermit');
            }
            if (!$order->relationLoaded('contractor')) {
                $order->load('contractor');
            }
            if (!$order->relationLoaded('department')) {
                $order->load('department');
            }

            $orderPermit = $order->orderPermit;
            if (!$orderPermit) {
                return;
            }

            $companyId = $udsRecords->first()->company_id;

            $contractorNames = $udsRecords->pluck('contractor_name')->filter()->unique()->values()->all();
            $contractorsByName = empty($contractorNames)
                ? collect()
                : ProjectContractor::withoutGlobalScopes()
                    ->where('company_id', $companyId)
                    ->whereIn('name', $contractorNames)
                    ->get()
                    ->keyBy('name');

            $departmentNames = $udsRecords->pluck('office')->filter()->unique()->values()->all();
            $departmentsByName = empty($departmentNames)
                ? collect()
                : OrderPermitDepartment::whereIn('name', $departmentNames)->get()->keyBy('name');

            $updates = [];
            $logMessages = [];

            foreach ($udsRecords as $uds) {
                $typeCode = $uds->type_code !== null ? trim((string) $uds->type_code) : '';
                if ($typeCode === '') {
                    continue;
                }

                $isContractor =
                    $orderPermit->code !== null
                    && (string) $orderPermit->code === $typeCode;

                $isConsultant =
                    $orderPermit->type !== null
                    && (string) $orderPermit->type === $typeCode;

                if (!$isContractor && !$isConsultant) {
                    continue;
                }

                if ($isContractor) {
                    $akValue = $uds->contractor_name !== null ? trim((string) $uds->contractor_name) : null;
                    $akValue = $akValue !== '' ? $akValue : null;
                    $currentContractor = $order->contractor;

                    if ($akValue !== null) {
                        if ($currentContractor) {
                            $currentName = $currentContractor->name;
                            if ($currentName !== $akValue) {
                                $newContractor = $contractorsByName->get($akValue);
                                if ($newContractor) {
                                    $updates['contractor_id'] = $newContractor->id;
                                    $updates['import_log'] = null;
                                } else {
                                    $msg = '[' . Carbon::now()->toDateTimeString() . "] Contractor name '{$akValue}' not found; kept '{$currentName}'.";
                                    Log::warning($msg);
                                    $logMessages = [$msg];
                                }
                            }
                        } else {
                            $newContractor = $contractorsByName->get($akValue);
                            if ($newContractor) {
                                $updates['contractor_id'] = $newContractor->id;
                                $updates['import_log'] = null;
                            } else {
                                $msg = '[' . Carbon::now()->toDateTimeString() . "] No contractor with name '{$akValue}' found.";
                                Log::warning($msg);
                                $logMessages = [$msg];
                            }
                        }
                    }
                }

                $departmentName = $uds->office !== null ? trim((string) $uds->office) : null;
                $departmentName = $departmentName !== '' ? $departmentName : null;

                if ($departmentName !== null) {
                    $currentDepartment = $order->department;
                    $currentDeptName = $currentDepartment?->name;

                    if ($currentDeptName !== $departmentName) {
                        $newDepartment = $departmentsByName->get($departmentName);
                        if ($newDepartment) {
                            $updates['order_permit_department_id'] = $newDepartment->id;
                            if (!array_key_exists('import_log', $updates) || $updates['import_log'] !== null) {
                                $updates['import_log'] = null;
                            }
                        } else {
                            $msg = '[' . Carbon::now()->toDateTimeString() . "] Department '{$departmentName}' not found; kept '{$currentDeptName}'.";
                            Log::warning($msg);
                            $logMessages[] = $msg;
                        }
                    }
                }

                if ($isContractor) {
                    $updates['executing_entity'] = $uds->executing_entity;
                    $updates['office'] = $uds->office;
                    $updates['contractor_basket'] = $uds->contractor_basket;
                    $updates['contractor_last_procedure_code'] = $uds->contractor_last_procedure_code;
                    $updates['contractor_last_procedure_date'] = $this->formatUdsDate($uds->contractor_last_procedure_date);
                    $updates['contractor_column_155_entry_date'] = $this->formatUdsDate($uds->contractor_column_155_entry_date);
                    $updates['material_balance_elec_contractor'] = $uds->material_balance_elec_contractor;
                    $updates['contractor_work_order_status'] = $uds->contractor_work_order_status;
                } else {
                    $updates['consultant_current_basket'] = $uds->consultant_current_basket;
                    $updates['assigned_date'] = $this->formatUdsDate($uds->assigned_date);
                    $updates['consultant_assignment_date'] = $this->formatUdsDate($uds->consultant_assignment_date);
                    $updates['consultant_last_procedure_code'] = $uds->consultant_last_procedure_code;
                    $updates['consultant_last_procedure_date'] = $this->formatUdsDate($uds->consultant_last_procedure_date);
                    $updates['consultant_column_155_entry_date'] = $this->formatUdsDate($uds->consultant_column_155_entry_date);
                    $updates['price'] = $uds->price;
                    $updates['consultant_price'] = $uds->consultant_price;
                }
            }

            if (empty($updates) && empty($logMessages)) {
                return;
            }

            $updates['last_row_update_at'] = Carbon::now();

            if (!empty($logMessages) && !array_key_exists('import_log', $updates)) {
                $updates['import_log'] = implode("\n", $logMessages);
            }

            ProjectOrderPermit::where('id', $order->id)->update($updates);
            $order->refresh();

            Log::info("Auto-filled order {$order->name} from project_order_permit_uds.", [
                'fields' => array_keys($updates),
            ]);
        } catch (\Exception $e) {
            Log::error("Auto-fill failed for order {$order->name}: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatUdsDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function list(string $projectId, array $filters = []): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->when(Arr::get($filters, 'order_permit_department_id'), fn ($q, $deptId) => $q->whereHas('orderPermit', fn ($subQuery) => $subQuery->where('order_permit_department_id', $deptId)))
            ->with(['orderPermit.department', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict', 'projectCompletionPhase', 'projectPhaseStatus', 'connectionCompletionPhase', 'connectionPhaseStatus', 'employee', 'noteLogs.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Autocomplete search against imported UDS work orders for a project.
     *
     * @return list<array<string, mixed>>
     */
    public function searchUds(string $projectId, string $search, int $limit = 20): array
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $search = trim($search);
        if ($search === '') {
            return [];
        }

        $limit = max(1, min($limit, 50));

        return ProjectOrderPermitUds::query()
            ->select('project_order_permit_uds.*')
            ->join('order_permit', 'order_permit.code', '=', 'project_order_permit_uds.type_code')
            ->where('project_order_permit_uds.project_id', $project->id)
            ->where('project_order_permit_uds.name', 'like', '%'.$search.'%')
            ->distinct()
            ->orderBy('project_order_permit_uds.name')
            ->limit($limit)
            ->get()
            ->map(static fn (ProjectOrderPermitUds $row): array => $row->toArray())
            ->values()
            ->all();
    }

    public function listAll(array $filters = []): Collection
    {
        $projectIds = ProjectManagement::query()->pluck('id');

        return ProjectOrderPermit::query()
            ->whereIn('project_id', $projectIds)
            ->when(Arr::get($filters, 'order_permit_department_id'), fn ($q, $deptId) => $q->whereHas('orderPermit', fn ($subQuery) => $subQuery->where('order_permit_department_id', $deptId)))
            ->with(['orderPermit.department', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict', 'projectCompletionPhase', 'projectPhaseStatus', 'connectionCompletionPhase', 'connectionPhaseStatus', 'employee', 'noteLogs.user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }


    public function show(string $projectId, string $id): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        return ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit.department', 'department', 'contractor', 'state', 'projectManagement', 'projectDistrict', 'projectCompletionPhase', 'projectPhaseStatus', 'connectionCompletionPhase', 'connectionPhaseStatus', 'employee', 'noteLogs.user'])
            ->firstOrFail();
    }


    public function update(string $projectId, string $id, array $data): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit.department'])
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

        $departmentName = $orderPermit->department?->name ?? $orderPermit->orderPermit?->department?->name;
        $phaseStatusId = Arr::get($data, 'phase_status_id');

        $phaseUpdateData = [];

        if ($phaseStatusId !== null) {
            if ($departmentName === 'مشاريع') {
                $status = ProjectPhaseStatus::find($phaseStatusId);
                if ($status) {
                    $phaseUpdateData['project_phase_status_id'] = $status->id;
                    $phaseUpdateData['project_completion_phase_id'] = $status->project_completion_phase_id;
                }
            } elseif ($departmentName === 'توصيلات') {
                $status = ConnectionPhaseStatus::find($phaseStatusId);
                if ($status) {
                    $phaseUpdateData['connection_phase_status_id'] = $status->id;
                    $phaseUpdateData['connection_completion_phase_id'] = $status->connection_completion_phase_id;
                }
            }
        } else {
            $phaseUpdateData['project_completion_phase_id'] = Arr::get($data, 'project_completion_phase_id', $orderPermit->project_completion_phase_id);
            $phaseUpdateData['project_phase_status_id'] = Arr::get($data, 'project_phase_status_id', $orderPermit->project_phase_status_id);
            $phaseUpdateData['connection_completion_phase_id'] = Arr::get($data, 'connection_completion_phase_id', $orderPermit->connection_completion_phase_id);
            $phaseUpdateData['connection_phase_status_id'] = Arr::get($data, 'connection_phase_status_id', $orderPermit->connection_phase_status_id);

            $completionPhaseId = Arr::get($data, 'completion_phase_id');
            if ($completionPhaseId !== null) {
                if ($departmentName === 'مشاريع') {
                    $phaseUpdateData['project_completion_phase_id'] = $completionPhaseId;
                } elseif ($departmentName === 'توصيلات') {
                    $phaseUpdateData['connection_completion_phase_id'] = $completionPhaseId;
                }
            }
        }

        $oldName = $orderPermit->name;

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
            'project_completion_phase_id' => $phaseUpdateData['project_completion_phase_id'] ?? $orderPermit->project_completion_phase_id,
            'project_phase_status_id' => $phaseUpdateData['project_phase_status_id'] ?? $orderPermit->project_phase_status_id,
            'connection_completion_phase_id' => $phaseUpdateData['connection_completion_phase_id'] ?? $orderPermit->connection_completion_phase_id,
            'connection_phase_status_id' => $phaseUpdateData['connection_phase_status_id'] ?? $orderPermit->connection_phase_status_id,
            'start_permit_date' => Arr::get($data, 'start_permit_date', $orderPermit->start_permit_date),
            'end_permit_date' => Arr::get($data, 'end_permit_date', $orderPermit->end_permit_date),
            'note_from_permit_to_departments' => Arr::get($data, 'note_from_permit_to_departments', $orderPermit->note_from_permit_to_departments),
            'note_from_departments_to_permit' => Arr::get($data, 'note_from_departments_to_permit', $orderPermit->note_from_departments_to_permit),
            'is_taked_action' => Arr::get($data, 'is_taked_action', $orderPermit->is_taked_action),
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
            'employee_id' => Arr::get($data, 'employee_id', $orderPermit->employee_id),
            'target_drilling' => Arr::get($data, 'target_drilling', $orderPermit->target_drilling),
            'achieved_drilling' => Arr::get($data, 'achieved_drilling', $orderPermit->achieved_drilling),
            'target_extention' => Arr::get($data, 'target_extention', $orderPermit->target_extention),
            'achieved_extention' => Arr::get($data, 'achieved_extention', $orderPermit->achieved_extention),
            'description_details' => Arr::get($data, 'description_details', $orderPermit->description_details),
            'consultant_statement' => Arr::get($data, 'consultant_statement', $orderPermit->consultant_statement),
            'last_date_consultant_statement' => Arr::get($data, 'last_date_consultant_statement', $orderPermit->last_date_consultant_statement),
            'consultnat_statement_status' => Arr::get($data, 'consultnat_statement_status', $orderPermit->consultnat_statement_status),
        ]);

        if ((string) $oldName !== (string) $newName) {
            $this->constructionArchiveFolders->syncWorkOrderFolderName(
                $orderPermit,
                (string) $oldName,
                (string) $newName,
            );
        } else {
            $this->constructionArchiveFolders->ensureWorkOrderFolder($orderPermit);
        }

        $this->createNoteLog($orderPermit, Arr::get($data, 'note_from_permit_to_departments'), ProjectOrderPermitNoteLog::TYPE_PERMIT_TO_DEPARTMENTS);
        $this->createNoteLog($orderPermit, Arr::get($data, 'note_from_departments_to_permit'), ProjectOrderPermitNoteLog::TYPE_DEPARTMENTS_TO_PERMIT);

        return $orderPermit->fresh([
            'orderPermit.department',
            'department',
            'contractor',
            'state',
            'projectManagement',
            'projectDistrict',
            'projectCompletionPhase',
            'projectPhaseStatus',
            'connectionCompletionPhase',
            'connectionPhaseStatus',
            'employee',
            'noteLogs.user',
        ]);
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

    private function createNoteLog(ProjectOrderPermit $orderPermit, ?string $note, string $type = ProjectOrderPermitNoteLog::TYPE_PERMIT_TO_DEPARTMENTS): void
    {
        if ($note === null || trim($note) === '') {
            return;
        }

        $timezone = getTimeZoneBranchByRequest() ?? config('app.timezone');
        $user = auth()->user();

        ProjectOrderPermitNoteLog::create([
            'project_order_permit_id' => $orderPermit->id,
            'user_id' => $user?->id,
            'note' => $note,
            'type' => $type,
            'timezone' => $timezone,
            'created_by_name' => $user?->name,
        ]);
    }

    public function getNoteLogs(string $projectId, string $id): Collection
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->firstOrFail();

        return $orderPermit->noteLogs()->with('user')->orderBy('created_at', 'desc')->get();
    }

    public function updateStatuses(string $projectId, string $id, array $data): ProjectOrderPermit
    {
        $project = ProjectManagement::withoutGlobalScopes()->findOrFail($projectId);

        $orderPermit = ProjectOrderPermit::query()
            ->where('project_id', $project->id)
            ->where('id', $id)
            ->with(['orderPermit.department'])
            ->firstOrFail();

        $departmentName = $orderPermit->department?->name ?? $orderPermit->orderPermit?->department?->name;
        $phaseStatusId = Arr::get($data, 'phase_status_id');

        $updateData = [];

        if ($phaseStatusId !== null) {
            if ($departmentName === 'مشاريع') {
                $status = ProjectPhaseStatus::find($phaseStatusId);
                if ($status) {
                    $updateData['project_phase_status_id'] = $status->id;
                    $updateData['project_completion_phase_id'] = $status->project_completion_phase_id;
                }
            } elseif ($departmentName === 'توصيلات') {
                $status = ConnectionPhaseStatus::find($phaseStatusId);
                if ($status) {
                    $updateData['connection_phase_status_id'] = $status->id;
                    $updateData['connection_completion_phase_id'] = $status->connection_completion_phase_id;
                }
            }
        }

        if (! empty($updateData)) {
            $orderPermit->update($updateData);
        }

        return $orderPermit->fresh([
            'orderPermit.department',
            'department',
            'contractor',
            'state',
            'projectManagement',
            'projectDistrict',
            'projectCompletionPhase',
            'projectPhaseStatus',
            'connectionCompletionPhase',
            'connectionPhaseStatus',
            'employee',
        ]);
    }
}
