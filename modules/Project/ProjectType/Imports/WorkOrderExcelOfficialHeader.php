<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Imports;

/**
 * Exact first-row header from the official Project Work Orders template.
 */
final class WorkOrderExcelOfficialHeader
{
    public const COLUMN_COUNT = 13;

    public const TEMPLATE_RELATIVE_PATH = 'modules/Project/ProjectType/Resources/templates/project-work-orders-import-template.xlsx';

    public const DOWNLOAD_NAME = 'project-work-orders-import-template.xlsx';

    /**
     * @var list<string>
     */
    public const COLUMNS = [
        'رقم أمر العمل',
        'نوع أمر العمل',
        'ملاحظات من قسم المشاريع أو التوصيلات إلى التصاريح',
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
    ];

    public static function templateAbsolutePath(): string
    {
        return base_path(self::TEMPLATE_RELATIVE_PATH);
    }
}
