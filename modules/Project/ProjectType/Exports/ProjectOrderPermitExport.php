<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Exports;

use App\Exports\BaseExport;
use Modules\Project\ProjectType\Presenters\ProjectOrderPermitPresenter;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;

/**
 * Exports the same project order-permit rows returned by ProjectOrderPermitService::list()
 * and ProjectOrderPermitPresenter, including its calculated fields.
 */
class ProjectOrderPermitExport extends BaseExport
{
    public function __construct(
        private readonly ProjectOrderPermitService $orderPermitService,
        private readonly string $projectId,
        array $filters = [],
    ) {
        $this->filters = $filters;
    }

    public function collection()
    {
        $items = $this->orderPermitService->list($this->projectId, $this->filters);

        $items->loadMissing([
            'orderPermit.orderPermitType',
            'orderPermit.department',
        ]);

        return $items;
    }

    public function headings(): array
    {
        return [
            'أمر العمل',
            'نوع أمر العمل',
            'أمر عمل الاستشاري',
            'اسم القسم',
            'الوصف',
            'الحفرية / العداد',
            'فترة UDS',
            'تاريخ الإسناد',
            'المقاول',
            'الإدارة',
            'الموقع',
            'خط العرض',
            'خط الطول',
            'السعر بالريال',
            'جهة التنفيذ',
            'المكتب',
            'سلة الجهة الحالية للاستشاري',
            'تاريخ الإسناد للاستشاري',
            'رمز آخر إجراء للاستشاري',
            'تاريخ آخر إجراء للاستشاري',
            'تاريخ إدخال عمود 155 للاستشاري',
            'رمز آخر إجراء للمقاول',
            'تاريخ آخر إجراء للمقاول',
            'تاريخ إدخال عمود 155 للمقاول',
            'توازن المواد بين الكهرباء والمقاول',
            'موقف أمر العمل للمقاول',
            'سلة جهة المقاول',
            'سعر الاستشاري',
            'حالة التصريح',
            'تاريخ بداية التصريح',
            'تاريخ نهاية التصريح',
            'ملاحظات من التصاريح إلى قسم المشاريع أو التوصيلات',
            'ملاحظات من قسم المشاريع أو التوصيلات إلى التصاريح',
            'عدد أيام منذ الإسناد',
            'تقييم الطلب أو التصريح',
            'المهندس المسؤول',
            'مرحلة التنفيذ',
            'حالة المرحلة',
            'الحفر المستهدف',
            'الحفر المنفذ',
            'التمديد المستهدف',
            'التمديد المنفذ',
            'شرح تفصيل أمر العمل',
            'إفادة الاستشاري',
            'تاريخ آخر إفادة',
            'مدة المشروع الرسمية',
            'عدد أيام حتى تنفيذ 155',
            'النسبة الزمنية',
            'نسبة إنجاز الحفر',
            'نسبة إنجاز التمديد',
        ];
    }

    public function map($row): array
    {
        $data = (new ProjectOrderPermitPresenter($row))->getData(true) ?? [];
        $orderPermit = is_array($data['order_permit'] ?? null) ? $data['order_permit'] : [];

        return [
            $this->cell($data['name'] ?? null),
            $this->cell($orderPermit['code'] ?? null),
            $this->cell($orderPermit['type'] ?? null),
            $this->cell($data['department_name'] ?? $orderPermit['department_name'] ?? null),
            $this->cell($orderPermit['description'] ?? null),
            $this->cell($orderPermit['order_permit_type_name'] ?? null),
            $this->cell($orderPermit['uds_period'] ?? null),
            $this->cell($data['assigned_date'] ?? null),
            $this->cell($data['contractor_name'] ?? null),
            $this->cell($data['project_management_name'] ?? null),
            $this->cell($data['projects_district_name'] ?? null),
            $this->cell($data['lat'] ?? null),
            $this->cell($data['long'] ?? null),
            $this->cell($data['price'] ?? null),
            $this->cell($data['executing_entity'] ?? null),
            $this->cell($data['office'] ?? null),
            $this->cell($data['consultant_current_basket'] ?? null),
            $this->cell($data['consultant_assignment_date'] ?? null),
            $this->cell($data['consultant_last_procedure_code'] ?? null),
            $this->cell($data['consultant_last_procedure_date'] ?? null),
            $this->cell($data['consultant_column_155_entry_date'] ?? null),
            $this->cell($data['contractor_last_procedure_code'] ?? null),
            $this->cell($data['contractor_last_procedure_date'] ?? null),
            $this->cell($data['contractor_column_155_entry_date'] ?? null),
            $this->cell($data['material_balance_elec_contractor'] ?? null),
            $this->cell($data['contractor_work_order_status'] ?? null),
            $this->cell($data['contractor_basket'] ?? null),
            $this->cell($data['consultant_price'] ?? null),
            $this->cell($data['permit_status_name'] ?? null),
            $this->cell($data['start_permit_date'] ?? null),
            $this->cell($data['end_permit_date'] ?? null),
            $this->cell($data['note_from_permit_to_departments'] ?? null),
            $this->cell($data['note_from_departments_to_permit'] ?? null),
            $this->cell($data['count_of_days_from_assigned_date'] ?? null),
            $this->cell($data['evaluation_permit_status'] ?? null),
            $this->cell($data['employee_name'] ?? null),
            $this->cell($data['completion_phase_name'] ?? null),
            $this->cell($data['phase_status_name'] ?? null),
            $this->cell($data['target_drilling'] ?? null),
            $this->cell($data['achieved_drilling'] ?? null),
            $this->cell($data['target_extention'] ?? null),
            $this->cell($data['achieved_extention'] ?? null),
            $this->cell($data['description_details'] ?? null),
            $this->cell($data['consultant_statement'] ?? null),
            $this->cell($data['last_date_consultant_statement'] ?? null),
            $this->cell($data['official_project_hours'] ?? null),
            $this->cell($data['number_of_days_to_achieve_column_155'] ?? null),
            $this->cell($data['percentage_time'] ?? null),
            $this->cell($data['percentage_achieve_drilling'] ?? null),
            $this->cell($data['percentage_achieve_extention'] ?? null),
        ];
    }

    public function getFilterableColumns(): array
    {
        return [
            'order_permit_department_id',
        ];
    }

    private function cell(mixed $value): mixed
    {
        return $value === null ? '' : $value;
    }
}
