<?php

declare(strict_types=1);

namespace Modules\Reports\Requests\Traits;

use Modules\Reports\DTO\ReportWizardConfigDTO;
use Modules\Reports\Enums\ReportEnums;

/**
 * Shared validation rules for the 5-step wizard payload. Both
 * `CreateReportRequest` and `CreateReportTemplateRequest` use these so the
 * contract with the frontend stays in one place.
 */
trait ValidatesWizardConfig
{
    /**
     * @return array<string,mixed>
     */
    protected function wizardConfigRules(string $prefix = 'config.'): array
    {
        $p = rtrim($prefix, '.');
        $p = $p === '' ? '' : $p . '.';

        $enum = fn (array $values): string => 'in:' . implode(',', $values);

        return [
            // ---- Step 1 --------------------------------------------------
            "{$p}step1"                      => 'required|array',
            "{$p}step1.reportTypeIds"        => 'required|array|min:1',
            "{$p}step1.reportTypeIds.*"      => ['required', 'string', $enum(ReportEnums::reportTypes())],
            "{$p}step1.periodType"           => ['required', 'string', $enum(ReportEnums::periodTypes())],
            "{$p}step1.year"                 => 'required|integer|min:2000|max:2100',
            "{$p}step1.month"                => 'nullable|integer|min:1|max:12',
            "{$p}step1.week"                 => 'nullable|integer|min:1|max:53',
            "{$p}step1.quarter"              => 'nullable|integer|min:1|max:4',
            "{$p}step1.dateFrom"             => 'nullable|date',
            "{$p}step1.dateTo"               => 'nullable|date',
            "{$p}step1.exportFormat"         => ['required', 'string', $enum(ReportEnums::exportFormats())],
            "{$p}step1.reportLanguage"       => ['required', 'string', $enum(ReportEnums::reportLanguages())],
            "{$p}step1.paperSize"            => ['required', 'string', $enum(ReportEnums::paperSizes())],
            "{$p}step1.printOrientation"     => ['required', 'string', $enum(ReportEnums::printOrientations())],

            // ---- Step 2 --------------------------------------------------
            "{$p}step2"                          => 'required|array',
            "{$p}step2.employee_scope"           => ['nullable', 'string', $enum(ReportEnums::employeeScopes())],
            "{$p}step2.employee_user_ids"        => 'nullable|array',
            "{$p}step2.employee_user_ids.*"      => 'string',
            "{$p}step2.branch_id"                => 'nullable|string|max:255',
            "{$p}step2.management_id"            => 'nullable|string|max:255',
            "{$p}step2.department"               => 'nullable|string|max:255',
            "{$p}step2.job_title"                => 'nullable|string|max:255',
            "{$p}step2.contractTypeIds"          => 'nullable|array',
            "{$p}step2.contractTypeIds.*"        => ['string', $enum(ReportEnums::contractTypes())],
            "{$p}step2.nationality"              => 'nullable|string|max:255',
            "{$p}step2.gender"                   => ['nullable', 'string', $enum(ReportEnums::genders())],

            // ---- Step 3 --------------------------------------------------
            "{$p}step3"                             => 'required|array',
            "{$p}step3.attendanceDataTypeIds"       => 'nullable|array',
            "{$p}step3.attendanceDataTypeIds.*"     => ['string', $enum(ReportEnums::attendanceDataTypes())],
            "{$p}step3.display_mode"                => ['nullable', 'string', $enum(ReportEnums::displayModes())],
            "{$p}step3.attendancePattern"           => ['required', 'string', $enum(ReportEnums::attendancePatterns())],
            "{$p}step3.attendanceRateMin"           => ['required', 'string', $enum(ReportEnums::attendanceRateOptions())],
            "{$p}step3.delayLimitMinutes"           => ['required', 'string', $enum(ReportEnums::delayLimitOptions())],
            "{$p}step3.minOvertime"                 => ['required', 'string', $enum(ReportEnums::minOvertimeOptions())],
            "{$p}step3.includeEntryExitTime"        => 'required|boolean',
            "{$p}step3.includeShiftName"            => 'required|boolean',
            "{$p}step3.includeAttendanceNotes"      => 'required|boolean',
            "{$p}step3.calculateTotalWorkHours"     => 'required|boolean',
            "{$p}step3.showPreviousMonthComparison" => 'required|boolean',

            // ---- Step 4 (optional — attendance-only reports omit it) -----
            "{$p}step4"                         => 'nullable|array',
            "{$p}step4.salaryComponentIds"      => 'nullable|array',
            "{$p}step4.salaryComponentIds.*"    => ['string', $enum(ReportEnums::salaryComponents())],
            "{$p}step4.deductionIds"            => 'nullable|array',
            "{$p}step4.deductionIds.*"          => ['string', $enum(ReportEnums::salaryDeductions())],
            "{$p}step4.disbursementStatus"      => ['nullable', 'string', $enum(ReportEnums::disbursementStatuses())],
            "{$p}step4.netSalaryOnly"               => 'nullable|boolean',
            "{$p}step4.compareWithPreviousMonth"    => 'nullable|boolean',
            "{$p}step4.employeeDetailsSeparatePage" => 'nullable|boolean',
            "{$p}step4.addTotalSummaryEnd"          => 'nullable|boolean',

            // ---- Step 5 (optional — attendance-only reports omit it) -----
            "{$p}step5"                       => 'nullable|array',
            "{$p}step5.mainSortBy"            => ['nullable', 'string', $enum(ReportEnums::mainSortByOptions())],
            "{$p}step5.sortDirection"         => ['nullable', 'string', $enum(ReportEnums::sortDirections())],
            "{$p}step5.groupBy"               => ['nullable', 'string', $enum(ReportEnums::groupByOptions())],
            "{$p}step5.employeesPerPage"      => ['nullable', 'string', $enum(ReportEnums::employeesPerPageOptions())],
            "{$p}step5.visualElementIds"      => 'nullable|array',
            "{$p}step5.visualElementIds.*"    => ['string', $enum(ReportEnums::visualElements())],
            "{$p}step5.autoEmail"             => 'nullable|boolean',
            "{$p}step5.copyToManager"         => 'nullable|boolean',
            "{$p}step5.monthlyScheduling"     => 'nullable|boolean',
            "{$p}step5.companyHeaderFooter"   => 'nullable|boolean',
            "{$p}step5.digitalSignature"      => 'nullable|boolean',
            "{$p}step5.recipientEmails"       => 'nullable|string|max:2000',
        ];
    }

    /**
     * Build an immutable DTO from the validated payload under a given key.
     */
    protected function buildWizardConfig(string $key = 'config'): ReportWizardConfigDTO
    {
        /** @var array $payload */
        $payload = $key === ''
            ? $this->validated()
            : ($this->input($key, []) ?? []);

        return ReportWizardConfigDTO::fromArray(is_array($payload) ? $payload : []);
    }
}
