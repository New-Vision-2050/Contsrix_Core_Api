<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Carbon\Carbon;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Models\ConnectionCompletionPhase;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermitNoteLog;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Support\WorkOrderExcelImportLogger;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Construction work-order Excel import — UPDATE ONLY.
 * Never inserts a new ProjectOrderPermit.
 */
final class ProjectWorkOrderExcelImportService
{
    private const DEPARTMENT_PROJECTS = 'مشاريع';

    private const DEPARTMENT_CONNECTIONS = 'توصيلات';

    /**
     * @param  array<int, array<int|string, mixed>>  $rows
     * @return array{
     *     updated: int,
     *     skipped: int,
     *     processed: int,
     *     total_data_rows: int,
     *     errors: list<array{row: int, work_order_name: ?string, reason: string}>,
     *     warnings: list<array{row: int, work_order_name: ?string, reason: string}>
     * }
     */
    public function importRows(
        array $rows,
        string $projectId,
        string $companyId,
        string $importId,
        ?string $actorUserId = null,
        ?string $actorUserName = null,
    ): array {
        $updated = 0;
        $skipped = 0;
        $processed = 0;
        $errors = [];
        $warnings = [];
        $employeesByName = $this->buildProjectEmployeeNameMap($projectId, $companyId);

        WorkOrderExcelImportLogger::info('employee_map_built', [
            'import_id' => $importId,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'unique_engineer_names' => count($employeesByName),
        ]);

        $totalDataRows = 0;

        foreach ($rows as $index => $row) {
            if ($index === 0 || $this->isEmptyRow($row)) {
                continue;
            }

            $totalDataRows++;
            $excelRow = $index + 1;
            $workOrderName = $this->peekWorkOrderName($row);

            try {
                $rowWarnings = $this->updateExistingWorkOrder(
                    $row,
                    $projectId,
                    $companyId,
                    $employeesByName,
                    $importId,
                    $excelRow,
                    $actorUserId,
                    $actorUserName,
                );
                $updated++;
                $processed++;

                foreach ($rowWarnings as $warning) {
                    $warnings[] = $warning;
                }

                WorkOrderExcelImportLogger::info('row_updated', [
                    'import_id' => $importId,
                    'project_id' => $projectId,
                    'company_id' => $companyId,
                    'row_number' => $excelRow,
                    'work_order_name' => $workOrderName,
                    'status' => 'updated',
                    'warning_count' => count($rowWarnings),
                ]);
            } catch (\DomainException $e) {
                $skipped++;
                $processed++;
                $errors[] = [
                    'row' => $excelRow,
                    'work_order_name' => $workOrderName,
                    'reason' => $e->getMessage(),
                ];

                WorkOrderExcelImportLogger::warning('row_skipped', [
                    'import_id' => $importId,
                    'project_id' => $projectId,
                    'company_id' => $companyId,
                    'row_number' => $excelRow,
                    'work_order_name' => $workOrderName,
                    'status' => 'skipped',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'processed' => $processed,
            'total_data_rows' => $totalDataRows,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @param  array<string, list<string>>  $employeesByName
     * @return list<array{row: int, work_order_name: ?string, reason: string}>
     */
    private function updateExistingWorkOrder(
        array $row,
        string $projectId,
        string $companyId,
        array $employeesByName,
        string $importId,
        int $excelRow,
        ?string $actorUserId,
        ?string $actorUserName,
    ): array {
        $warnings = [];
        $workOrderName = $this->requiredText($row, 0, 'رقم أمر العمل');
        $orderPermitCode = $this->requiredText($row, 1, 'نوع أمر العمل');

        $orderPermits = OrderPermit::query()
            ->where('code', $orderPermitCode)
            ->with('department:id,name')
            ->get(['id', 'code', 'order_permit_department_id'])
            ->filter(static fn (OrderPermit $permit): bool => (string) $permit->code === $orderPermitCode)
            ->values();

        if ($orderPermits->isEmpty()) {
            throw new \DomainException("نوع أمر العمل غير موجود: {$orderPermitCode}");
        }

        if ($orderPermits->count() !== 1) {
            throw new \DomainException("نوع أمر العمل غير فريد: {$orderPermitCode}");
        }

        /** @var OrderPermit $orderPermit */
        $orderPermit = $orderPermits->first();

        $matches = ProjectOrderPermit::query()
            ->where('project_id', $projectId)
            ->where('name', $workOrderName)
            ->where('order_permit_id', $orderPermit->id)
            ->with([
                'orderPermit:id,code,order_permit_department_id',
                'orderPermit.department:id,name',
                'department:id,name',
            ])
            ->get()
            ->filter(
                static fn (ProjectOrderPermit $permit): bool => (string) $permit->name === $workOrderName
                    && (string) $permit->orderPermit?->code === $orderPermitCode
            )
            ->values();

        WorkOrderExcelImportLogger::info('row_match_lookup', [
            'import_id' => $importId,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'row_number' => $excelRow,
            'work_order_name' => $workOrderName,
            'order_permit_code' => $orderPermitCode,
            'matches_found' => $matches->count(),
            'status' => 'lookup',
        ]);

        if ($matches->isEmpty()) {
            throw new \DomainException(
                "أمر العمل غير موجود للمشروع الحالي والرقم {$workOrderName} والنوع {$orderPermitCode}"
            );
        }

        if ($matches->count() > 1) {
            throw new \DomainException(
                "يوجد أكثر من أمر عمل مطابق للمشروع الحالي والرقم {$workOrderName} والنوع {$orderPermitCode}"
            );
        }

        /** @var ProjectOrderPermit $workOrder */
        $workOrder = $matches->first();

        $phaseName = $this->requiredText($row, 4, 'مرحلة التنفيذ');
        $statusName = $this->requiredText($row, 5, 'حالة المرحلة');

        $departmentName = $workOrder->department?->name
            ?? $orderPermit->department?->name;
        $departmentId = $workOrder->order_permit_department_id
            ?? $orderPermit->order_permit_department_id;
        $phaseFields = $this->resolvePhaseAndStatusFields($departmentName, $departmentId, $phaseName, $statusName);

        $note = $this->nullableText($row, 2);
        $employeeName = $this->nullableText($row, 3);
        $employeeResolution = $this->resolveEmployeeIdOptionally($employeesByName, $employeeName);

        if ($employeeResolution['warning'] !== null) {
            $warning = [
                'row' => $excelRow,
                'work_order_name' => $workOrderName,
                'reason' => $employeeResolution['warning'],
            ];
            $warnings[] = $warning;

            WorkOrderExcelImportLogger::warning('engineer_not_applied', [
                'import_id' => $importId,
                'project_id' => $projectId,
                'company_id' => $companyId,
                'row_number' => $excelRow,
                'work_order_name' => $workOrderName,
                'engineer_name' => $employeeName,
                'status' => 'warning',
                'error_message' => $employeeResolution['warning'],
            ]);
        }

        $payload = [
            'order_permit_id' => $orderPermit->id,
            'order_permit_department_id' => $departmentId,
            'note_from_departments_to_permit' => $note,
            ...$phaseFields,
            'target_drilling' => $this->nullableNumber($row, 6, 'الحفر المستهدف'),
            'achieved_drilling' => $this->nullableNumber($row, 7, 'الحفر المنفذ'),
            'target_extention' => $this->nullableNumber($row, 8, 'التمديد المستهدف'),
            'achieved_extention' => $this->nullableNumber($row, 9, 'التمديد المنفذ'),
            'description_details' => $this->nullableText($row, 10),
            'consultant_statement' => $this->nullableText($row, 11),
            'last_date_consultant_statement' => $this->nullableDate($row, 12),
        ];

        // Only change employee_id when the engineer resolves uniquely on this project.
        // Never create users/employees; leave existing employee_id untouched otherwise.
        if ($employeeResolution['employee_id'] !== null) {
            $payload['employee_id'] = $employeeResolution['employee_id'];
        }

        WorkOrderExcelImportLogger::info('row_update_started', [
            'import_id' => $importId,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'row_number' => $excelRow,
            'work_order_name' => $workOrderName,
            'project_order_permit_id' => $workOrder->id,
            'employee_applied' => $employeeResolution['employee_id'] !== null,
            'status' => 'updating',
        ]);

        $workOrder->update($payload);

        $this->createDepartmentsNoteLog(
            $workOrder,
            $note,
            $actorUserId,
            $actorUserName,
            $importId,
            $excelRow,
        );

        return $warnings;
    }

    /**
     * @return array<string, list<string>>
     */
    private function buildProjectEmployeeNameMap(string $projectId, string $companyId): array
    {
        $map = [];

        $employees = ProjectEmployee::query()
            ->where('project_id', $projectId)
            ->where('company_id', $companyId)
            ->with(['user' => static fn ($query) => $query->withoutGlobalScopes()->select('id', 'name')])
            ->get(['user_id']);

        foreach ($employees as $employee) {
            $name = $this->normalizeText((string) ($employee->user?->name ?? ''));
            if ($name === '') {
                continue;
            }

            $map[$name][] = (string) $employee->user_id;
        }

        foreach ($map as $name => $ids) {
            $map[$name] = array_values(array_unique($ids));
        }

        return $map;
    }

    /**
     * Resolve engineer for update without failing the row.
     * Does not create users or project employees.
     *
     * @param  array<string, list<string>>  $employeesByName
     * @return array{employee_id: ?string, warning: ?string}
     */
    private function resolveEmployeeIdOptionally(array $employeesByName, ?string $employeeName): array
    {
        if ($employeeName === null) {
            return [
                'employee_id' => null,
                'warning' => 'المهندس المسؤول فاضي في ملف الإكسل؛ تم تحديث باقي الحقول بدون تغيير المهندس',
            ];
        }

        $ids = $employeesByName[$employeeName] ?? [];

        if ($ids === []) {
            return [
                'employee_id' => null,
                'warning' => "المهندس غير موجود في قاعدة البيانات أو غير مربوط بالمشروع الحالي: {$employeeName}",
            ];
        }

        if (count($ids) !== 1) {
            return [
                'employee_id' => null,
                'warning' => "المهندس غير محدد بشكل فريد في المشروع: {$employeeName}",
            ];
        }

        return [
            'employee_id' => $ids[0],
            'warning' => null,
        ];
    }

    /**
     * @return array{
     *     project_completion_phase_id?: int,
     *     project_phase_status_id?: int,
     *     connection_completion_phase_id?: int,
     *     connection_phase_status_id?: int
     * }
     */
    private function resolvePhaseAndStatusFields(
        ?string $departmentName,
        int|string|null $departmentId,
        string $phaseName,
        string $statusName,
    ): array {
        if ($departmentName === self::DEPARTMENT_PROJECTS) {
            $phase = ProjectCompletionPhase::query()
                ->when(
                    $departmentId !== null,
                    static fn ($query) => $query->where('order_permit_department_id', $departmentId),
                )
                ->get(['id', 'name'])
                ->first(fn (ProjectCompletionPhase $phase): bool => $this->normalizeText((string) $phase->name) === $phaseName);

            if ($phase === null) {
                throw new \DomainException("مرحلة التنفيذ غير موجودة: {$phaseName}");
            }

            $status = ProjectPhaseStatus::query()
                ->where('project_completion_phase_id', $phase->id)
                ->get(['id', 'name'])
                ->first(fn (ProjectPhaseStatus $status): bool => $this->normalizeText((string) $status->name) === $statusName);

            if ($status === null) {
                throw new \DomainException("حالة المرحلة غير موجودة: {$statusName}");
            }

            return [
                'project_completion_phase_id' => $phase->id,
                'project_phase_status_id' => $status->id,
            ];
        }

        if ($departmentName === self::DEPARTMENT_CONNECTIONS) {
            $phase = ConnectionCompletionPhase::query()
                ->when(
                    $departmentId !== null,
                    static fn ($query) => $query->where('order_permit_department_id', $departmentId),
                )
                ->get(['id', 'name'])
                ->first(fn (ConnectionCompletionPhase $phase): bool => $this->normalizeText((string) $phase->name) === $phaseName);

            if ($phase === null) {
                throw new \DomainException("مرحلة التنفيذ غير موجودة: {$phaseName}");
            }

            $status = ConnectionPhaseStatus::query()
                ->where('connection_completion_phase_id', $phase->id)
                ->get(['id', 'name'])
                ->first(fn (ConnectionPhaseStatus $status): bool => $this->normalizeText((string) $status->name) === $statusName);

            if ($status === null) {
                throw new \DomainException("حالة المرحلة غير موجودة: {$statusName}");
            }

            return [
                'connection_completion_phase_id' => $phase->id,
                'connection_phase_status_id' => $status->id,
            ];
        }

        throw new \DomainException(
            'قسم أمر العمل غير مدعوم لاستيراد المرحلة/الحالة: '.($departmentName ?: 'غير معروف')
        );
    }

    private function createDepartmentsNoteLog(
        ProjectOrderPermit $workOrder,
        ?string $note,
        ?string $actorUserId,
        ?string $actorUserName,
        string $importId,
        int $excelRow,
    ): void {
        if ($note === null || trim($note) === '') {
            WorkOrderExcelImportLogger::info('note_log_skipped_empty', [
                'import_id' => $importId,
                'row_number' => $excelRow,
                'project_order_permit_id' => $workOrder->id,
                'status' => 'skipped',
            ]);

            return;
        }

        ProjectOrderPermitNoteLog::query()->create([
            'project_order_permit_id' => $workOrder->id,
            'user_id' => $actorUserId,
            'note' => $note,
            'type' => ProjectOrderPermitNoteLog::TYPE_DEPARTMENTS_TO_PERMIT,
            'timezone' => getTimeZoneBranchByRequest() ?? config('app.timezone'),
            'created_by_name' => $actorUserName,
        ]);

        WorkOrderExcelImportLogger::info('note_log_created', [
            'import_id' => $importId,
            'row_number' => $excelRow,
            'project_order_permit_id' => $workOrder->id,
            'user_id' => $actorUserId,
            'status' => 'created',
        ]);
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function peekWorkOrderName(array $row): ?string
    {
        try {
            return $this->nullableText($row, 0);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function requiredText(array $row, int $index, string $label): string
    {
        $value = $this->nullableText($row, $index);

        if ($value === null) {
            throw new \DomainException("الحقل مطلوب: {$label}");
        }

        return $value;
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function nullableText(array $row, int $index): ?string
    {
        $value = $row[$index] ?? null;

        if ($value === null) {
            return null;
        }

        if (is_float($value) && floor($value) === $value) {
            $value = (int) $value;
        }

        $value = $this->normalizeText((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeText(string $value): string
    {
        $value = str_replace(["\u{00A0}", "\u{202F}", "\u{2007}"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function nullableNumber(array $row, int $index, string $label): int|float|null
    {
        $value = $this->nullableText($row, $index);

        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new \DomainException("قيمة رقمية غير صالحة في الحقل {$label}: {$value}");
        }

        return (float) $value;
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function nullableDate(array $row, int $index): ?string
    {
        $raw = $row[$index] ?? null;

        if ($raw === null || trim((string) $raw) === '') {
            return null;
        }

        try {
            $date = is_numeric($raw)
                ? Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw))
                : Carbon::parse((string) $raw);

            return $date->format('Y-m-d');
        } catch (\Throwable) {
            throw new \DomainException("تاريخ آخر إفادة غير صالح: {$raw}");
        }
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
