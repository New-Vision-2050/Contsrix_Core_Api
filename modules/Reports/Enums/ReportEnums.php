<?php

declare(strict_types=1);

namespace Modules\Reports\Enums;

/**
 * Single source of truth for every enumerable string id used by the reports
 * wizard. The arrays returned by each method are the canonical lists used
 * for `in:` validation rules AND for the lookup endpoint that powers the
 * frontend dropdowns/checkboxes.
 *
 * Keep these values in sync with `ReportWizardPayload` on the frontend:
 *   - step1.reportTypeIds[]              -> reportTypes()
 *   - step1.periodType                   -> periodTypes()
 *   - step1.exportFormat                 -> exportFormats()
 *   - step1.reportLanguage               -> reportLanguages()
 *   - step1.paperSize                    -> paperSizes()
 *   - step1.printOrientation             -> printOrientations()
 *   - step2.employeeStatus               -> employeeStatuses()
 *   - step2.contractTypeIds[]            -> contractTypes()
 *   - step3.attendanceDataTypeIds[]      -> attendanceDataTypes()
 *   - step3.attendancePattern            -> attendancePatterns()
 *   - step3.attendanceRateMin            -> attendanceRateOptions()
 *   - step3.delayLimitMinutes            -> delayLimitOptions()
 *   - step3.minOvertime                  -> minOvertimeOptions()
 *   - step4.salaryComponentIds[]         -> salaryComponents()
 *   - step4.deductionIds[]               -> salaryDeductions()
 *   - step4.disbursementStatus           -> disbursementStatuses()
 *   - step5.mainSortBy                   -> mainSortByOptions()
 *   - step5.sortDirection                -> sortDirections()
 *   - step5.groupBy                      -> groupByOptions()
 *   - step5.employeesPerPage             -> employeesPerPageOptions()
 *   - step5.visualElementIds[]           -> visualElements()
 */
final class ReportEnums
{
    // ---- Step 1 ----------------------------------------------------------
    public const REPORT_TYPE_ATTENDANCE_ABSENCE = 'attendance_absence';
    public const REPORT_TYPE_LEAVES             = 'leaves';
    public const REPORT_TYPE_OVERTIME            = 'overtime';
    public const REPORT_TYPE_MONTHLY_PERFORMANCE = 'monthly_performance';
    public const REPORT_TYPE_SALARIES            = 'salaries';
    public const REPORT_TYPE_LATENESS            = 'lateness';
    public const REPORT_TYPE_DEDUCTIONS          = 'deductions';
    public const REPORT_TYPE_BRANCHES_COMPARISON = 'branches_comparison';

    public const PERIOD_MONTHLY   = 'monthly';
    public const PERIOD_WEEKLY    = 'weekly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_YEARLY    = 'yearly';

    public const FORMAT_PDF   = 'pdf';
    public const FORMAT_EXCEL = 'excel';
    public const FORMAT_CSV   = 'csv';

    public const LANGUAGE_AR = 'ar';
    public const LANGUAGE_EN = 'en';

    public const PAPER_A4     = 'A4';
    public const PAPER_LETTER = 'Letter';
    public const PAPER_A3     = 'A3';

    public const ORIENTATION_PORTRAIT  = 'portrait';
    public const ORIENTATION_LANDSCAPE = 'landscape';

    // ---- Step 2 ----------------------------------------------------------
    public const EMPLOYEE_STATUS_ALL       = 'all';
    public const EMPLOYEE_STATUS_ACTIVE    = 'active';
    public const EMPLOYEE_STATUS_INACTIVE  = 'inactive';
    public const EMPLOYEE_STATUS_ON_LEAVE  = 'on_leave';
    public const EMPLOYEE_STATUS_DISMISSED = 'dismissed';

    public const CONTRACT_FULL_TIME           = 'full_time';
    public const CONTRACT_PART_TIME           = 'part_time';
    public const CONTRACT_TEMPORARY           = 'temporary';
    public const CONTRACT_INTERN              = 'intern';
    public const CONTRACT_EXTERNAL_CONSULTANT = 'external_consultant';
    public const CONTRACT_SEASONAL            = 'seasonal';

    // ---- Step 3 ----------------------------------------------------------
    public const ATT_DATA_ATTENDANCE_DAYS  = 'attendance_days';
    public const ATT_DATA_DELAYS           = 'delays';
    public const ATT_DATA_TAKEN_LEAVES     = 'taken_leaves';
    public const ATT_DATA_UNPAID_LEAVE     = 'unpaid_leave';
    public const ATT_DATA_ABSENCE_DAYS     = 'absence_days';
    public const ATT_DATA_OVERTIME         = 'overtime';
    public const ATT_DATA_SICK_LEAVES      = 'sick_leaves';
    public const ATT_DATA_EARLY_DEPARTURE  = 'early_departure';

    public const ATT_PATTERN_ALL                = 'all';
    public const ATT_PATTERN_ABSENTEES_ONLY     = 'absentees_only';
    public const ATT_PATTERN_LATE_ONLY          = 'late_only';
    public const ATT_PATTERN_OVERTIME_ONLY      = 'overtime_only';
    public const ATT_PATTERN_PRESENT_ONLY       = 'present_only';

    public const ATT_RATE_NO_FILTER       = 'no_filter';
    public const ATT_RATE_FIFTY           = 'fifty';
    public const ATT_RATE_SEVENTY         = 'seventy';
    public const ATT_RATE_NINETY          = 'ninety';

    public const DELAY_NO_FILTER          = 'no_filter';
    public const DELAY_FIVE_MIN_OR_MORE   = 'five_min_or_more';
    public const DELAY_FIFTEEN_MIN_OR_MORE = 'fifteen_min_or_more';
    public const DELAY_THIRTY_MIN_OR_MORE = 'thirty_min_or_more';
    public const DELAY_SIXTY_MIN_OR_MORE  = 'sixty_min_or_more';

    public const OT_NO_FILTER             = 'no_filter';
    public const OT_HALF_HOUR_OR_MORE     = 'half_hour_or_more';
    public const OT_ONE_HOUR_OR_MORE      = 'one_hour_or_more';
    public const OT_TWO_HOURS_OR_MORE     = 'two_hours_or_more';
    public const OT_FOUR_HOURS_OR_MORE    = 'four_hours_or_more';

    // ---- Step 4 ----------------------------------------------------------
    public const SALARY_BASIC_SALARY        = 'basic_salary';
    public const SALARY_TRANSPORTATION      = 'transportation';
    public const SALARY_PHONE               = 'phone';
    public const SALARY_OVERTIME_ALLOWANCE  = 'overtime_allowance';
    public const SALARY_HOUSING             = 'housing';
    public const SALARY_FOOD                = 'food';
    public const SALARY_REPRESENTATION      = 'representation';
    public const SALARY_BONUSES             = 'bonuses';

    public const DEDUCTION_ABSENCE          = 'absence_deduction';
    public const DEDUCTION_SOCIAL_INSURANCE = 'social_insurance';
    public const DEDUCTION_DISCIPLINARY     = 'disciplinary';
    public const DEDUCTION_DELAY            = 'delay_deduction';
    public const DEDUCTION_ADVANCES_LOANS   = 'advances_loans';
    public const DEDUCTION_INCOME_TAX       = 'income_tax';

    public const DISBURSEMENT_ALL              = 'all';
    public const DISBURSEMENT_DISBURSED        = 'disbursed';
    public const DISBURSEMENT_PENDING_APPROVAL = 'pending_approval';
    public const DISBURSEMENT_SUSPENDED        = 'suspended';

    // ---- Step 5 ----------------------------------------------------------
    public const SORT_BY_EMPLOYEE_NAME_ALPHA = 'employee_name_alpha';
    public const SORT_BY_EMPLOYEE_CODE       = 'employee_code';
    public const SORT_BY_DEPARTMENT          = 'department';
    public const SORT_BY_BRANCH              = 'branch';
    public const SORT_BY_JOB_TITLE           = 'job_title';
    public const SORT_BY_HIRE_DATE           = 'hire_date';

    public const SORT_DIR_ASC  = 'asc';
    public const SORT_DIR_DESC = 'desc';

    public const GROUP_BY_NONE       = 'none';
    public const GROUP_BY_BRANCH     = 'branch';
    public const GROUP_BY_DEPARTMENT = 'department';
    public const GROUP_BY_MANAGEMENT = 'management';
    public const GROUP_BY_JOB_TITLE  = 'job_title';

    public const VISUAL_ATTENDANCE_PCT_CHART       = 'attendance_pct_chart';
    public const VISUAL_WEEKLY_DELAYS_CHART        = 'weekly_delays_chart';
    public const VISUAL_EXECUTIVE_SUMMARY_TABLE    = 'executive_summary_table';
    public const VISUAL_SALARY_DISTRIBUTION_CHART  = 'salary_distribution_chart';
    public const VISUAL_BRANCH_COMPARISON_CHART    = 'branch_comparison_chart';
    public const VISUAL_ATTENDANCE_HEATMAP         = 'attendance_heatmap';

    // ---- Lookup arrays --------------------------------------------------

    public static function reportTypes(): array
    {
        return [
            self::REPORT_TYPE_ATTENDANCE_ABSENCE,
            self::REPORT_TYPE_LEAVES,
            self::REPORT_TYPE_OVERTIME,
            self::REPORT_TYPE_MONTHLY_PERFORMANCE,
            self::REPORT_TYPE_SALARIES,
            self::REPORT_TYPE_LATENESS,
            self::REPORT_TYPE_DEDUCTIONS,
            self::REPORT_TYPE_BRANCHES_COMPARISON,
        ];
    }

    public static function periodTypes(): array
    {
        return [self::PERIOD_MONTHLY, self::PERIOD_WEEKLY, self::PERIOD_QUARTERLY, self::PERIOD_YEARLY];
    }

    public static function exportFormats(): array
    {
        return [self::FORMAT_PDF, self::FORMAT_EXCEL, self::FORMAT_CSV];
    }

    public static function reportLanguages(): array
    {
        return [self::LANGUAGE_AR, self::LANGUAGE_EN];
    }

    public static function paperSizes(): array
    {
        return [self::PAPER_A4, self::PAPER_LETTER, self::PAPER_A3];
    }

    public static function printOrientations(): array
    {
        return [self::ORIENTATION_PORTRAIT, self::ORIENTATION_LANDSCAPE];
    }

    public static function employeeStatuses(): array
    {
        return [
            self::EMPLOYEE_STATUS_ALL,
            self::EMPLOYEE_STATUS_ACTIVE,
            self::EMPLOYEE_STATUS_INACTIVE,
            self::EMPLOYEE_STATUS_ON_LEAVE,
            self::EMPLOYEE_STATUS_DISMISSED,
        ];
    }

    public static function contractTypes(): array
    {
        return [
            self::CONTRACT_FULL_TIME,
            self::CONTRACT_PART_TIME,
            self::CONTRACT_TEMPORARY,
            self::CONTRACT_INTERN,
            self::CONTRACT_EXTERNAL_CONSULTANT,
            self::CONTRACT_SEASONAL,
        ];
    }

    public static function attendanceDataTypes(): array
    {
        return [
            self::ATT_DATA_ATTENDANCE_DAYS,
            self::ATT_DATA_DELAYS,
            self::ATT_DATA_TAKEN_LEAVES,
            self::ATT_DATA_UNPAID_LEAVE,
            self::ATT_DATA_ABSENCE_DAYS,
            self::ATT_DATA_OVERTIME,
            self::ATT_DATA_SICK_LEAVES,
            self::ATT_DATA_EARLY_DEPARTURE,
        ];
    }

    public static function attendancePatterns(): array
    {
        return [
            self::ATT_PATTERN_ALL,
            self::ATT_PATTERN_ABSENTEES_ONLY,
            self::ATT_PATTERN_LATE_ONLY,
            self::ATT_PATTERN_OVERTIME_ONLY,
            self::ATT_PATTERN_PRESENT_ONLY,
        ];
    }

    public static function attendanceRateOptions(): array
    {
        return [
            self::ATT_RATE_NO_FILTER,
            self::ATT_RATE_FIFTY,
            self::ATT_RATE_SEVENTY,
            self::ATT_RATE_NINETY,
        ];
    }

    public static function delayLimitOptions(): array
    {
        return [
            self::DELAY_NO_FILTER,
            self::DELAY_FIVE_MIN_OR_MORE,
            self::DELAY_FIFTEEN_MIN_OR_MORE,
            self::DELAY_THIRTY_MIN_OR_MORE,
            self::DELAY_SIXTY_MIN_OR_MORE,
        ];
    }

    public static function minOvertimeOptions(): array
    {
        return [
            self::OT_NO_FILTER,
            self::OT_HALF_HOUR_OR_MORE,
            self::OT_ONE_HOUR_OR_MORE,
            self::OT_TWO_HOURS_OR_MORE,
            self::OT_FOUR_HOURS_OR_MORE,
        ];
    }

    public static function salaryComponents(): array
    {
        return [
            self::SALARY_BASIC_SALARY,
            self::SALARY_TRANSPORTATION,
            self::SALARY_PHONE,
            self::SALARY_OVERTIME_ALLOWANCE,
            self::SALARY_HOUSING,
            self::SALARY_FOOD,
            self::SALARY_REPRESENTATION,
            self::SALARY_BONUSES,
        ];
    }

    public static function salaryDeductions(): array
    {
        return [
            self::DEDUCTION_ABSENCE,
            self::DEDUCTION_SOCIAL_INSURANCE,
            self::DEDUCTION_DISCIPLINARY,
            self::DEDUCTION_DELAY,
            self::DEDUCTION_ADVANCES_LOANS,
            self::DEDUCTION_INCOME_TAX,
        ];
    }

    public static function disbursementStatuses(): array
    {
        return [
            self::DISBURSEMENT_ALL,
            self::DISBURSEMENT_DISBURSED,
            self::DISBURSEMENT_PENDING_APPROVAL,
            self::DISBURSEMENT_SUSPENDED,
        ];
    }

    public static function mainSortByOptions(): array
    {
        return [
            self::SORT_BY_EMPLOYEE_NAME_ALPHA,
            self::SORT_BY_EMPLOYEE_CODE,
            self::SORT_BY_DEPARTMENT,
            self::SORT_BY_BRANCH,
            self::SORT_BY_JOB_TITLE,
            self::SORT_BY_HIRE_DATE,
        ];
    }

    public static function sortDirections(): array
    {
        return [self::SORT_DIR_ASC, self::SORT_DIR_DESC];
    }

    public static function groupByOptions(): array
    {
        return [
            self::GROUP_BY_NONE,
            self::GROUP_BY_BRANCH,
            self::GROUP_BY_DEPARTMENT,
            self::GROUP_BY_MANAGEMENT,
            self::GROUP_BY_JOB_TITLE,
        ];
    }

    public static function employeesPerPageOptions(): array
    {
        return ['10', '25', '50', '100'];
    }

    public static function visualElements(): array
    {
        return [
            self::VISUAL_ATTENDANCE_PCT_CHART,
            self::VISUAL_WEEKLY_DELAYS_CHART,
            self::VISUAL_EXECUTIVE_SUMMARY_TABLE,
            self::VISUAL_SALARY_DISTRIBUTION_CHART,
            self::VISUAL_BRANCH_COMPARISON_CHART,
            self::VISUAL_ATTENDANCE_HEATMAP,
        ];
    }

    public static function genders(): array
    {
        return ['male', 'female'];
    }
}
