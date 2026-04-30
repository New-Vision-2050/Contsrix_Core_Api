<?php

declare(strict_types=1);

namespace Modules\Reports\Services;

use Modules\Reports\Enums\ReportEnums;

/**
 * Provides the static option catalog that powers every dropdown / multi-select
 * / radio in the 5-step wizard. The frontend reads this once and caches it.
 *
 * Each option is returned as `{id, label: {ar, en}}` so the UI can render the
 * correct locale without hard-coding the translation tables.
 */
class ReportLookupService
{
    public function all(): array
    {
        return [
            'report_types'          => $this->reportTypes(),
            'period_types'          => $this->periodTypes(),
            'export_formats'        => $this->exportFormats(),
            'report_languages'      => $this->reportLanguages(),
            'paper_sizes'           => $this->paperSizes(),
            'print_orientations'    => $this->printOrientations(),
            'employee_statuses'     => $this->employeeStatuses(),
            'contract_types'        => $this->contractTypes(),
            'genders'               => $this->genders(),
            'attendance_data_types' => $this->attendanceDataTypes(),
            'attendance_patterns'   => $this->attendancePatterns(),
            'attendance_rate_min'   => $this->attendanceRateOptions(),
            'delay_limits'          => $this->delayLimitOptions(),
            'min_overtime'          => $this->minOvertimeOptions(),
            'salary_components'     => $this->salaryComponents(),
            'salary_deductions'     => $this->salaryDeductions(),
            'disbursement_statuses' => $this->disbursementStatuses(),
            'main_sort_by'          => $this->mainSortByOptions(),
            'sort_directions'       => $this->sortDirections(),
            'group_by'              => $this->groupByOptions(),
            'employees_per_page'    => $this->employeesPerPageOptions(),
            'visual_elements'       => $this->visualElements(),
        ];
    }

    public function reportTypes(): array
    {
        return $this->build(ReportEnums::reportTypes(), [
            ReportEnums::REPORT_TYPE_ATTENDANCE_ABSENCE => ['ar' => 'الحضور والغياب', 'en' => 'Attendance & Absence'],
            ReportEnums::REPORT_TYPE_LEAVES             => ['ar' => 'الإجازات',        'en' => 'Leaves'],
            ReportEnums::REPORT_TYPE_OVERTIME           => ['ar' => 'الساعات الإضافية', 'en' => 'Overtime'],
            ReportEnums::REPORT_TYPE_MONTHLY_PERFORMANCE=> ['ar' => 'الأداء الشهري',    'en' => 'Monthly Performance'],
            ReportEnums::REPORT_TYPE_SALARIES           => ['ar' => 'الرواتب',          'en' => 'Salaries'],
            ReportEnums::REPORT_TYPE_LATENESS           => ['ar' => 'التأخيرات',        'en' => 'Lateness'],
            ReportEnums::REPORT_TYPE_DEDUCTIONS         => ['ar' => 'الخصومات',         'en' => 'Deductions'],
            ReportEnums::REPORT_TYPE_BRANCHES_COMPARISON=> ['ar' => 'مقارنة الفروع',    'en' => 'Branches Comparison'],
        ]);
    }

    public function periodTypes(): array
    {
        return $this->build(ReportEnums::periodTypes(), [
            ReportEnums::PERIOD_MONTHLY   => ['ar' => 'شهري',   'en' => 'Monthly'],
            ReportEnums::PERIOD_WEEKLY    => ['ar' => 'أسبوعي', 'en' => 'Weekly'],
            ReportEnums::PERIOD_QUARTERLY => ['ar' => 'ربعي',   'en' => 'Quarterly'],
            ReportEnums::PERIOD_YEARLY    => ['ar' => 'سنوي',   'en' => 'Yearly'],
        ]);
    }

    public function exportFormats(): array
    {
        return $this->build(ReportEnums::exportFormats(), [
            ReportEnums::FORMAT_PDF   => ['ar' => 'PDF',   'en' => 'PDF'],
            ReportEnums::FORMAT_EXCEL => ['ar' => 'Excel', 'en' => 'Excel'],
            ReportEnums::FORMAT_CSV   => ['ar' => 'CSV',   'en' => 'CSV'],
        ]);
    }

    public function reportLanguages(): array
    {
        return $this->build(ReportEnums::reportLanguages(), [
            ReportEnums::LANGUAGE_AR => ['ar' => 'العربية',    'en' => 'Arabic'],
            ReportEnums::LANGUAGE_EN => ['ar' => 'الإنجليزية', 'en' => 'English'],
        ]);
    }

    public function paperSizes(): array
    {
        return $this->build(ReportEnums::paperSizes(), [
            ReportEnums::PAPER_A4     => ['ar' => 'A4',     'en' => 'A4'],
            ReportEnums::PAPER_LETTER => ['ar' => 'Letter', 'en' => 'Letter'],
            ReportEnums::PAPER_A3     => ['ar' => 'A3',     'en' => 'A3'],
        ]);
    }

    public function printOrientations(): array
    {
        return $this->build(ReportEnums::printOrientations(), [
            ReportEnums::ORIENTATION_PORTRAIT  => ['ar' => 'عمودي', 'en' => 'Portrait'],
            ReportEnums::ORIENTATION_LANDSCAPE => ['ar' => 'أفقي',  'en' => 'Landscape'],
        ]);
    }

    public function employeeStatuses(): array
    {
        return $this->build(ReportEnums::employeeStatuses(), [
            ReportEnums::EMPLOYEE_STATUS_ALL       => ['ar' => 'الكل',      'en' => 'All'],
            ReportEnums::EMPLOYEE_STATUS_ACTIVE    => ['ar' => 'نشط',       'en' => 'Active'],
            ReportEnums::EMPLOYEE_STATUS_INACTIVE  => ['ar' => 'غير نشط',   'en' => 'Inactive'],
            ReportEnums::EMPLOYEE_STATUS_ON_LEAVE  => ['ar' => 'في إجازة',  'en' => 'On Leave'],
            ReportEnums::EMPLOYEE_STATUS_DISMISSED => ['ar' => 'مفصول',     'en' => 'Dismissed'],
        ]);
    }

    public function contractTypes(): array
    {
        return $this->build(ReportEnums::contractTypes(), [
            ReportEnums::CONTRACT_FULL_TIME           => ['ar' => 'دوام كامل',        'en' => 'Full time'],
            ReportEnums::CONTRACT_PART_TIME           => ['ar' => 'دوام جزئي',        'en' => 'Part time'],
            ReportEnums::CONTRACT_TEMPORARY           => ['ar' => 'مؤقت',             'en' => 'Temporary'],
            ReportEnums::CONTRACT_INTERN              => ['ar' => 'متدرب',            'en' => 'Intern'],
            ReportEnums::CONTRACT_EXTERNAL_CONSULTANT => ['ar' => 'استشاري خارجي',    'en' => 'External consultant'],
            ReportEnums::CONTRACT_SEASONAL            => ['ar' => 'موسمي',            'en' => 'Seasonal'],
        ]);
    }

    public function genders(): array
    {
        return $this->build(ReportEnums::genders(), [
            'male'   => ['ar' => 'ذكر',  'en' => 'Male'],
            'female' => ['ar' => 'أنثى', 'en' => 'Female'],
        ]);
    }

    public function attendanceDataTypes(): array
    {
        return $this->build(ReportEnums::attendanceDataTypes(), [
            ReportEnums::ATT_DATA_ATTENDANCE_DAYS => ['ar' => 'أيام الحضور',       'en' => 'Attendance days'],
            ReportEnums::ATT_DATA_DELAYS          => ['ar' => 'التأخيرات',         'en' => 'Delays'],
            ReportEnums::ATT_DATA_TAKEN_LEAVES    => ['ar' => 'الإجازات المأخوذة', 'en' => 'Taken leaves'],
            ReportEnums::ATT_DATA_UNPAID_LEAVE    => ['ar' => 'الإجازات بدون راتب', 'en' => 'Unpaid leaves'],
            ReportEnums::ATT_DATA_ABSENCE_DAYS    => ['ar' => 'أيام الغياب',       'en' => 'Absence days'],
            ReportEnums::ATT_DATA_OVERTIME        => ['ar' => 'الساعات الإضافية',  'en' => 'Overtime'],
            ReportEnums::ATT_DATA_SICK_LEAVES     => ['ar' => 'الإجازات المرضية',  'en' => 'Sick leaves'],
            ReportEnums::ATT_DATA_EARLY_DEPARTURE => ['ar' => 'الانصراف المبكر',   'en' => 'Early departure'],
        ]);
    }

    public function attendancePatterns(): array
    {
        return $this->build(ReportEnums::attendancePatterns(), [
            ReportEnums::ATT_PATTERN_ALL            => ['ar' => 'الكل',              'en' => 'All'],
            ReportEnums::ATT_PATTERN_ABSENTEES_ONLY => ['ar' => 'الغائبون فقط',      'en' => 'Absentees only'],
            ReportEnums::ATT_PATTERN_LATE_ONLY      => ['ar' => 'المتأخرون فقط',     'en' => 'Late only'],
            ReportEnums::ATT_PATTERN_OVERTIME_ONLY  => ['ar' => 'العاملون إضافي فقط', 'en' => 'Overtime only'],
            ReportEnums::ATT_PATTERN_PRESENT_ONLY   => ['ar' => 'الحاضرون فقط',      'en' => 'Present only'],
        ]);
    }

    public function attendanceRateOptions(): array
    {
        return $this->build(ReportEnums::attendanceRateOptions(), [
            ReportEnums::ATT_RATE_NO_FILTER => ['ar' => 'بدون تصفية', 'en' => 'No filter'],
            ReportEnums::ATT_RATE_FIFTY     => ['ar' => '50٪ فأكثر',  'en' => '50% or more'],
            ReportEnums::ATT_RATE_SEVENTY   => ['ar' => '70٪ فأكثر',  'en' => '70% or more'],
            ReportEnums::ATT_RATE_NINETY    => ['ar' => '90٪ فأكثر',  'en' => '90% or more'],
        ]);
    }

    public function delayLimitOptions(): array
    {
        return $this->build(ReportEnums::delayLimitOptions(), [
            ReportEnums::DELAY_NO_FILTER          => ['ar' => 'بدون تصفية',          'en' => 'No filter'],
            ReportEnums::DELAY_FIVE_MIN_OR_MORE   => ['ar' => '5 دقائق فأكثر',       'en' => '5 minutes or more'],
            ReportEnums::DELAY_FIFTEEN_MIN_OR_MORE=> ['ar' => '15 دقيقة فأكثر',      'en' => '15 minutes or more'],
            ReportEnums::DELAY_THIRTY_MIN_OR_MORE => ['ar' => '30 دقيقة فأكثر',      'en' => '30 minutes or more'],
            ReportEnums::DELAY_SIXTY_MIN_OR_MORE  => ['ar' => '60 دقيقة فأكثر',      'en' => '60 minutes or more'],
        ]);
    }

    public function minOvertimeOptions(): array
    {
        return $this->build(ReportEnums::minOvertimeOptions(), [
            ReportEnums::OT_NO_FILTER          => ['ar' => 'بدون تصفية',      'en' => 'No filter'],
            ReportEnums::OT_HALF_HOUR_OR_MORE  => ['ar' => 'نصف ساعة فأكثر',  'en' => 'Half hour or more'],
            ReportEnums::OT_ONE_HOUR_OR_MORE   => ['ar' => 'ساعة فأكثر',      'en' => 'One hour or more'],
            ReportEnums::OT_TWO_HOURS_OR_MORE  => ['ar' => 'ساعتان فأكثر',    'en' => 'Two hours or more'],
            ReportEnums::OT_FOUR_HOURS_OR_MORE => ['ar' => '4 ساعات فأكثر',   'en' => 'Four hours or more'],
        ]);
    }

    public function salaryComponents(): array
    {
        return $this->build(ReportEnums::salaryComponents(), [
            ReportEnums::SALARY_BASIC_SALARY       => ['ar' => 'الراتب الأساسي',  'en' => 'Basic salary'],
            ReportEnums::SALARY_TRANSPORTATION     => ['ar' => 'بدل مواصلات',     'en' => 'Transportation'],
            ReportEnums::SALARY_PHONE              => ['ar' => 'بدل هاتف',        'en' => 'Phone'],
            ReportEnums::SALARY_OVERTIME_ALLOWANCE => ['ar' => 'بدل إضافي',       'en' => 'Overtime allowance'],
            ReportEnums::SALARY_HOUSING            => ['ar' => 'بدل سكن',         'en' => 'Housing'],
            ReportEnums::SALARY_FOOD               => ['ar' => 'بدل غذاء',        'en' => 'Food'],
            ReportEnums::SALARY_REPRESENTATION     => ['ar' => 'بدل تمثيل',       'en' => 'Representation'],
            ReportEnums::SALARY_BONUSES            => ['ar' => 'المكافآت',        'en' => 'Bonuses'],
        ]);
    }

    public function salaryDeductions(): array
    {
        return $this->build(ReportEnums::salaryDeductions(), [
            ReportEnums::DEDUCTION_ABSENCE          => ['ar' => 'خصم الغياب',       'en' => 'Absence deduction'],
            ReportEnums::DEDUCTION_SOCIAL_INSURANCE => ['ar' => 'التأمينات الاجتماعية','en' => 'Social insurance'],
            ReportEnums::DEDUCTION_DISCIPLINARY     => ['ar' => 'جزاءات تأديبية',   'en' => 'Disciplinary'],
            ReportEnums::DEDUCTION_DELAY            => ['ar' => 'خصم تأخير',        'en' => 'Delay deduction'],
            ReportEnums::DEDUCTION_ADVANCES_LOANS   => ['ar' => 'سلف وقروض',         'en' => 'Advances & loans'],
            ReportEnums::DEDUCTION_INCOME_TAX       => ['ar' => 'ضريبة الدخل',      'en' => 'Income tax'],
        ]);
    }

    public function disbursementStatuses(): array
    {
        return $this->build(ReportEnums::disbursementStatuses(), [
            ReportEnums::DISBURSEMENT_ALL              => ['ar' => 'الكل',                'en' => 'All'],
            ReportEnums::DISBURSEMENT_DISBURSED        => ['ar' => 'مصروف',               'en' => 'Disbursed'],
            ReportEnums::DISBURSEMENT_PENDING_APPROVAL => ['ar' => 'بانتظار الاعتماد',    'en' => 'Pending approval'],
            ReportEnums::DISBURSEMENT_SUSPENDED        => ['ar' => 'موقوف',               'en' => 'Suspended'],
        ]);
    }

    public function mainSortByOptions(): array
    {
        return $this->build(ReportEnums::mainSortByOptions(), [
            ReportEnums::SORT_BY_EMPLOYEE_NAME_ALPHA => ['ar' => 'اسم الموظف (أبجدي)', 'en' => 'Employee name (alphabetical)'],
            ReportEnums::SORT_BY_EMPLOYEE_CODE       => ['ar' => 'رقم الموظف',         'en' => 'Employee code'],
            ReportEnums::SORT_BY_DEPARTMENT          => ['ar' => 'القسم',              'en' => 'Department'],
            ReportEnums::SORT_BY_BRANCH              => ['ar' => 'الفرع',              'en' => 'Branch'],
            ReportEnums::SORT_BY_JOB_TITLE           => ['ar' => 'المسمى الوظيفي',     'en' => 'Job title'],
            ReportEnums::SORT_BY_HIRE_DATE           => ['ar' => 'تاريخ التوظيف',      'en' => 'Hire date'],
        ]);
    }

    public function sortDirections(): array
    {
        return $this->build(ReportEnums::sortDirections(), [
            ReportEnums::SORT_DIR_ASC  => ['ar' => 'تصاعدي', 'en' => 'Ascending'],
            ReportEnums::SORT_DIR_DESC => ['ar' => 'تنازلي', 'en' => 'Descending'],
        ]);
    }

    public function groupByOptions(): array
    {
        return $this->build(ReportEnums::groupByOptions(), [
            ReportEnums::GROUP_BY_NONE       => ['ar' => 'بدون', 'en' => 'None'],
            ReportEnums::GROUP_BY_BRANCH     => ['ar' => 'الفرع', 'en' => 'Branch'],
            ReportEnums::GROUP_BY_DEPARTMENT => ['ar' => 'القسم', 'en' => 'Department'],
            ReportEnums::GROUP_BY_MANAGEMENT => ['ar' => 'الإدارة', 'en' => 'Management'],
            ReportEnums::GROUP_BY_JOB_TITLE  => ['ar' => 'المسمى الوظيفي', 'en' => 'Job title'],
        ]);
    }

    public function employeesPerPageOptions(): array
    {
        return $this->build(ReportEnums::employeesPerPageOptions(), [
            '10'  => ['ar' => '10',  'en' => '10'],
            '25'  => ['ar' => '25',  'en' => '25'],
            '50'  => ['ar' => '50',  'en' => '50'],
            '100' => ['ar' => '100', 'en' => '100'],
        ]);
    }

    public function visualElements(): array
    {
        return $this->build(ReportEnums::visualElements(), [
            ReportEnums::VISUAL_ATTENDANCE_PCT_CHART      => ['ar' => 'مخطط نسبة الحضور',        'en' => 'Attendance percentage chart'],
            ReportEnums::VISUAL_WEEKLY_DELAYS_CHART       => ['ar' => 'مخطط التأخيرات الأسبوعية', 'en' => 'Weekly delays chart'],
            ReportEnums::VISUAL_EXECUTIVE_SUMMARY_TABLE   => ['ar' => 'جدول ملخص تنفيذي',         'en' => 'Executive summary table'],
            ReportEnums::VISUAL_SALARY_DISTRIBUTION_CHART => ['ar' => 'مخطط توزيع الرواتب',       'en' => 'Salary distribution chart'],
            ReportEnums::VISUAL_BRANCH_COMPARISON_CHART   => ['ar' => 'مخطط مقارنة الفروع',       'en' => 'Branch comparison chart'],
            ReportEnums::VISUAL_ATTENDANCE_HEATMAP        => ['ar' => 'خريطة حرارية للحضور',       'en' => 'Attendance heatmap'],
        ]);
    }

    /**
     * @param string[] $ids
     * @param array<string,array{ar:string,en:string}> $labels
     */
    private function build(array $ids, array $labels): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[] = [
                'id'    => $id,
                'label' => $labels[$id] ?? ['ar' => $id, 'en' => $id],
            ];
        }
        return $out;
    }
}
