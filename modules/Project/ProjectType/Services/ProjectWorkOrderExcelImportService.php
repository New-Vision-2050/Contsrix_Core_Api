<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Project\ProjectManagement\Models\ProjectEmployee;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

final class ProjectWorkOrderExcelImportService
{
    /**
     * @param  array<int, array<int|string, mixed>>  $rows
     * @return array{updated: int, skipped: int, errors: list<array{row: int, reason: string}>}
     */
    public function importRows(array $rows, string $projectId, string $companyId): array
    {
        $updated = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if ($index === 0 || $this->isEmptyRow($row)) {
                continue;
            }

            $excelRow = $index + 1;

            try {
                $this->updateExistingWorkOrder($row, $projectId, $companyId);
                $updated++;
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

        return compact('updated', 'skipped', 'errors');
    }

    /**
     * @param  array<int|string, mixed>  $row
     */
    private function updateExistingWorkOrder(array $row, string $projectId, string $companyId): void
    {
        $workOrderName = $this->requiredText($row, 0, 'رقم أمر العمل');
        $orderPermitCode = $this->requiredText($row, 1, 'نوع أمر العمل');

        $orderPermits = OrderPermit::query()
            ->where('code', $orderPermitCode)
            ->get(['id', 'code'])
            ->filter(static fn (OrderPermit $permit): bool => (string) $permit->code === $orderPermitCode)
            ->values();

        if ($orderPermits->isEmpty()) {
            throw new \DomainException("نوع أمر العمل غير موجود: {$orderPermitCode}");
        }

        $matches = ProjectOrderPermit::query()
            ->where('project_id', $projectId)
            ->where('name', $workOrderName)
            ->whereIn('order_permit_id', $orderPermits->pluck('id'))
            ->with('orderPermit:id,code,order_permit_department_id')
            ->get()
            ->filter(
                static fn (ProjectOrderPermit $permit): bool => (string) $permit->name === $workOrderName
                    && (string) $permit->orderPermit?->code === $orderPermitCode
            )
            ->values();

        if ($matches->isEmpty()) {
            throw new \DomainException(
                "أمر العمل غير موجود للمشروع الحالي والرقم {$workOrderName} والنوع {$orderPermitCode}"
            );
        }

        if ($matches->count() !== 1) {
            throw new \DomainException(
                "يوجد أكثر من أمر عمل مطابق للمشروع الحالي والرقم {$workOrderName} والنوع {$orderPermitCode}"
            );
        }

        /** @var ProjectOrderPermit $workOrder */
        $workOrder = $matches->first();
        $employeeName = $this->requiredText($row, 3, 'المهندس المسؤول');
        $phaseName = $this->requiredText($row, 4, 'مرحلة التنفيذ');
        $statusName = $this->requiredText($row, 5, 'حالة المرحلة');

        $employeeIds = ProjectEmployee::query()
            ->join('users', 'users.id', '=', 'project_employees.user_id')
            ->where('project_employees.project_id', $projectId)
            ->where('project_employees.company_id', $companyId)
            ->where('users.name', $employeeName)
            ->whereNull('users.deleted_at')
            ->get(['project_employees.user_id', 'users.name'])
            ->filter(static fn (ProjectEmployee $employee): bool => (string) $employee->name === $employeeName)
            ->pluck('user_id')
            ->unique()
            ->values();

        if ($employeeIds->count() !== 1) {
            throw new \DomainException("المهندس غير موجود أو غير محدد بشكل فريد في المشروع: {$employeeName}");
        }

        $phase = ProjectCompletionPhase::query()
            ->where('name', $phaseName)
            ->get(['id', 'name'])
            ->filter(static fn (ProjectCompletionPhase $phase): bool => (string) $phase->name === $phaseName)
            ->first();
        $phaseId = $phase?->id;
        $statusId = null;

        if ($phaseId !== null) {
            $status = ProjectPhaseStatus::query()
                ->where('project_completion_phase_id', $phaseId)
                ->where('name', $statusName)
                ->get(['id', 'name'])
                ->filter(static fn (ProjectPhaseStatus $status): bool => (string) $status->name === $statusName)
                ->first();
            $statusId = $status?->id;
        }

        $workOrder->update([
            'order_permit_id' => $workOrder->order_permit_id,
            'note_from_departments_to_permit' => $this->nullableText($row, 2),
            'employee_id' => $employeeIds->first(),
            'project_completion_phase_id' => $phaseId,
            'project_phase_status_id' => $statusId,
            'target_drilling' => $this->nullableNumber($row, 6, 'الحفر المستهدف'),
            'achieved_drilling' => $this->nullableNumber($row, 7, 'الحفر المنفذ'),
            'target_extention' => $this->nullableNumber($row, 8, 'التمديد المستهدف'),
            'achieved_extention' => $this->nullableNumber($row, 9, 'التمديد المنفذ'),
            'description_details' => $this->nullableText($row, 10),
            'consultant_statement' => $this->nullableText($row, 11),
            'last_date_consultant_statement' => $this->nullableDate($row, 12),
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

        $value = trim((string) $value);

        return $value === '' ? null : $value;
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
