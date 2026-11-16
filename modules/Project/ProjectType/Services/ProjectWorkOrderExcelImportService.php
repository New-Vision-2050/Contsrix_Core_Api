<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Models\ConnectionCompletionPhase;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectOrderPermitNoteLog;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class ProjectWorkOrderExcelImportService
{
    private const DEPARTMENT_PROJECTS = 'مشاريع';

    private const DEPARTMENT_CONNECTIONS = 'توصيلات';

    public function __construct(
        private readonly ConstructionArchiveFolderService $constructionArchiveFolders,
    ) {}

    /**
     * @param  array<int, array<int|string, mixed>>  $rows
     * @return array{updated: int, created: int, skipped: int, errors: list<array{row: int, reason: string}>}
     */
    public function importRows(array $rows, string $projectId, string $companyId): array
    {
        $updated = 0;
        $created = 0;
        $skipped = 0;
        $errors = [];
        $employeesByName = $this->buildProjectEmployeeNameMap($projectId, $companyId);

        foreach ($rows as $index => $row) {
            if ($index === 0 || $this->isEmptyRow($row)) {
                continue;
            }

            $excelRow = $index + 1;

            try {
                $action = $this->upsertWorkOrder($row, $projectId, $companyId, $employeesByName);

                if ($action === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            } catch (\DomainException $e) {
                $skipped++;
                $errors[] = ['row' => $excelRow, 'reason' => $e->getMessage()];

                Log::warning('Project Work Order Excel row skipped', [
                    'project_id' => $projectId,
                    'excel_row' => $excelRow,
                    'reason' => $e->getMessage(),
                ]);
            }
        }

        return compact('updated', 'created', 'skipped', 'errors');
    }

    /**
     * @param  array<int|string, mixed>  $row
     * @param  array<string, list<string>>  $employeesByName
     * @return 'updated'|'created'
     */
    private function upsertWorkOrder(array $row, string $projectId, string $companyId, array $employeesByName): string
    {
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

        if ($matches->count() > 1) {
            throw new \DomainException(
                "يوجد أكثر من أمر عمل مطابق للمشروع الحالي والرقم {$workOrderName} والنوع {$orderPermitCode}"
            );
        }

        $employeeName = $this->requiredText($row, 3, 'المهندس المسؤول');
        $phaseName = $this->requiredText($row, 4, 'مرحلة التنفيذ');
        $statusName = $this->requiredText($row, 5, 'حالة المرحلة');
        $employeeId = $this->resolveEmployeeId($employeesByName, $employeeName);

        $departmentName = $orderPermit->department?->name;
        $departmentId = $orderPermit->order_permit_department_id;
        $phaseFields = $this->resolvePhaseAndStatusFields($departmentName, $departmentId, $phaseName, $statusName);

        $payload = [
            'order_permit_id' => $orderPermit->id,
            'order_permit_department_id' => $departmentId,
            'note_from_departments_to_permit' => $this->nullableText($row, 2),
            'employee_id' => $employeeId,
            ...$phaseFields,
            'target_drilling' => $this->nullableNumber($row, 6, 'الحفر المستهدف'),
            'achieved_drilling' => $this->nullableNumber($row, 7, 'الحفر المنفذ'),
            'target_extention' => $this->nullableNumber($row, 8, 'التمديد المستهدف'),
            'achieved_extention' => $this->nullableNumber($row, 9, 'التمديد المنفذ'),
            'description_details' => $this->nullableText($row, 10),
            'consultant_statement' => $this->nullableText($row, 11),
            'last_date_consultant_statement' => $this->nullableDate($row, 12),
        ];

        if ($matches->isEmpty()) {
            $workOrder = ProjectOrderPermit::query()->create([
                'project_id' => $projectId,
                'name' => $workOrderName,
                ...$payload,
            ]);

            $this->createDepartmentsNoteLog($workOrder, $payload['note_from_departments_to_permit'] ?? null);
            $this->constructionArchiveFolders->ensureWorkOrderFolder($workOrder);

            return 'created';
        }

        /** @var ProjectOrderPermit $workOrder */
        $workOrder = $matches->first();
        $workOrder->update($payload);

        return 'updated';
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
     * @param  array<string, list<string>>  $employeesByName
     */
    private function resolveEmployeeId(array $employeesByName, string $employeeName): string
    {
        $ids = $employeesByName[$employeeName] ?? [];

        if (count($ids) !== 1) {
            throw new \DomainException("المهندس غير موجود أو غير محدد بشكل فريد في المشروع: {$employeeName}");
        }

        return $ids[0];
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

    private function createDepartmentsNoteLog(ProjectOrderPermit $workOrder, ?string $note): void
    {
        if ($note === null || trim($note) === '') {
            return;
        }

        ProjectOrderPermitNoteLog::query()->create([
            'project_order_permit_id' => $workOrder->id,
            'user_id' => auth()->id(),
            'note' => $note,
            'type' => ProjectOrderPermitNoteLog::TYPE_DEPARTMENTS_TO_PERMIT,
            'timezone' => getTimeZoneBranchByRequest() ?? config('app.timezone'),
            'created_by_name' => auth()->user()?->name,
        ]);
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
        // Excel often uses NBSP / odd unicode spaces that break exact DB matches.
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
