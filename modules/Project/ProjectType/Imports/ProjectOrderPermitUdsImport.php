<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

use Carbon\Carbon;

/**
 * Maps one UDS Excel row → one project_order_permit_uds record.
 *
 * Excel has two rows per work order (contractor + consultant).
 * Each DB row is an exact copy of ONE Excel row — never merge both roles into one record.
 *
 * Role-specific columns are filled only for that row's role (via type_code classification).
 *
 * Official Header validation is separate (UdsExcelOfficialHeader). These 0-based
 * indexes are the existing import mapping and must not be derived from Header names.
 */
class ProjectOrderPermitUdsImport
{
    /**
     * Detect a header row (e.g. Arabic/English column titles in the work-order column).
     */
    public function isHeaderRow(array $row): bool
    {
        $name = $this->value($row, 34);
        if ($name === null) {
            return false;
        }

        $normalized = mb_strtolower($name);

        return str_contains($normalized, 'أمر العمل')
            || str_contains($normalized, 'رقم أمر')
            || str_contains($normalized, 'work order')
            || str_contains($normalized, 'work_order')
            || str_contains($normalized, 'work order number');
    }

    /**
     * @param  bool  $isContractorRow  type_code matches an OrderPermit.code
     * @param  bool  $isConsultantRow  type_code matches an OrderPermit.type
     */
    public function mapRow(
        array $row,
        string $projectId,
        string $companyId,
        string $id,
        string $now,
        bool $isContractorRow = false,
        bool $isConsultantRow = false,
    ): ?array {
        if ($this->isHeaderRow($row)) {
            return null;
        }

        $name = $this->value($row, 34); // AI — رقم أمر العمل
        if ($name === null) {
            return null;
        }

        $typeCode = $this->value($row, 35); // AJ — رمز نوع أمر العمل
        $basket = $this->value($row, 16); // Q — الجهة الحالية
        $price = $this->parseFloat($row, 12); // M — تكلفة العمالة (existing: consultant price)
        $assignmentDate = $this->parseDate($row, 25); // Z — تاريخ التسليم للمقاول
        $column155Date = $this->parseDate($row, 24); // Y — تاريخ الاستلام من المقاول
        $lastProcedureDate = $this->parseDate($row, 28); // AC — تاريخ آخر إجراء
        $lastProcedureCode = $this->value($row, 30); // AE — رمز آخر إجراء

        // Shared Excel columns — present on every row (exact copy)
        $record = [
            'id' => $id,
            'project_id' => $projectId,
            'company_id' => $companyId,
            'name' => $name,
            'type_code' => $typeCode,
            'executing_entity' => $this->value($row, 27), // AB — جهة التنفيذ
            'office' => $this->value($row, 37), // AL — المكتب
            'current_entity' => $basket, // Q — الجهة الحالية
            'material_balance_elec_contractor' => $this->value($row, 13), // N — تكلفة المواد الغير مصروفة
            'contractor_work_order_status' => $this->value($row, 6), // G — موقف أمر العمل
            'contractor_name' => $this->value($row, 36), // AK — المقاول
            'penalty_amount' => $this->value($row, 0), // A — مقدار الغرامة
            'finance_approval_date' => $this->parseDate($row, 1), // B — تاريخ اعتماد المالية
            'certificate_source_number' => $this->value($row, 2), // C — رقم  مصدر الشهادة
            'modifier_employee_number' => $this->value($row, 3), // D — رقم الموظف المعدل
            'contractor_assigned_employee_number' => $this->value($row, 4), // E — رقم الموظف المسند للمقاول
            'work_order_status' => $this->value($row, 5), // F — حالة أمر العمل
            'work_order_situation' => $this->value($row, 6), // G — موقف أمر العمل
            'penalty_percentage' => $this->value($row, 7), // H — نسبة الغرامة
            'delay_duration' => $this->value($row, 8), // I — مدة التأخير
            'disbursement_status' => $this->value($row, 9), // J — حالة الصرف
            'total_cost' => $this->value($row, 10), // K — إجمالي التكلفة
            'indirect_cost' => $this->value($row, 11), // L — التكلفة الغير مباشرة
            'labor_cost' => $this->value($row, 31), // AF — نوع امر العمل (existing index, not M)
            'consumed_material_cost' => $this->value($row, 14), // O — تكلفة المواد المصروفة
            'office_code' => $this->value($row, 15), // P — رمز المكتب
            'cost_center_name' => $this->value($row, 17), // R — اسم مركز التكلفة
            'cost_center' => $this->value($row, 18), // S — مركز التكلفة
            'extract_number' => $this->value($row, 19), // T — رقم المستخلص
            'completion_certificate_amount' => $this->value($row, 20), // U — مبلغ شهادة الإنجاز
            'contractor_approval_cert_date' => $this->parseDate($row, 21), // V — تاريخ اعتماد المقاول لشهادة الانجاز
            'certificate_approval_date' => $this->parseDate($row, 22), // W — تاريخ اعتماد شهادة الانجاز
            'certificate_date' => $this->parseDate($row, 23), // X — تاريخ شهادة الانجاز
            'procedure_203_date' => $this->parseDate($row, 26), // AA — تاريخ اجراء 203
            'last_procedure_name' => $this->value($row, 29), // AD — مسمى آخر إجراء
            'work_order_type' => $this->value($row, 33), // AH — نوع المشترك
            'contract_number' => $this->value($row, 32), // AG — رقم العقد
            'subscriber_type' => null, // AH exists on the template; existing mapping leaves this unused
            'created_at' => $now,
            'updated_at' => $now,

            // Role-specific — null unless this Excel row is that role
            'contractor_basket' => null,
            'consultant_current_basket' => null,
            'assigned_date' => null,
            'consultant_assignment_date' => null,
            'contractor_last_procedure_code' => null,
            'contractor_last_procedure_date' => null,
            'contractor_column_155_entry_date' => null,
            'consultant_last_procedure_code' => null,
            'consultant_last_procedure_date' => null,
            'consultant_column_155_entry_date' => null,
            'price' => null,
            'consultant_price' => null,
        ];

        if ($isContractorRow) {
            $record['contractor_basket'] = $basket;
            $record['contractor_last_procedure_code'] = $lastProcedureCode;
            $record['contractor_last_procedure_date'] = $lastProcedureDate;
            $record['contractor_column_155_entry_date'] = $column155Date;
        }

        if ($isConsultantRow) {
            $record['consultant_current_basket'] = $basket;
            $record['assigned_date'] = $assignmentDate;
            $record['consultant_assignment_date'] = $assignmentDate;
            $record['consultant_last_procedure_code'] = $lastProcedureCode;
            $record['consultant_last_procedure_date'] = $lastProcedureDate;
            $record['consultant_column_155_entry_date'] = $column155Date;
            $record['price'] = $price;
            $record['consultant_price'] = $price;
        }

        // Unknown role: still keep Excel cell values on shared columns only
        // (current_entity, office, etc. already set). Role fields stay null.

        return $record;
    }

    private function value(array $row, int $index): ?string
    {
        $val = trim((string) ($row[$index] ?? ''));

        return $val !== '' ? $val : null;
    }

    private function parseDate(array $row, int $index): ?string
    {
        $val = $this->value($row, $index);
        if ($val === null) {
            return null;
        }

        try {
            return Carbon::parse($val)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseFloat(array $row, int $index): ?float
    {
        $val = $this->value($row, $index);
        if ($val === null) {
            return null;
        }

        return (float) $val;
    }
}
