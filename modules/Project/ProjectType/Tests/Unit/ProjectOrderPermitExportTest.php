<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Modules\Project\ProjectManagement\Models\ProjectContractor;
use Modules\Project\ProjectType\Exports\ProjectOrderPermitExport;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\OrderPermitType;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectDistrict;
use Modules\Project\ProjectType\Models\ProjectManagement;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;
use Modules\Project\ProjectType\Services\ProjectOrderPermitService;
use Modules\User\Models\User;
use Tests\TestCase;

final class ProjectOrderPermitExportTest extends TestCase
{
    public function test_collection_uses_the_same_list_filters_as_the_index_endpoint(): void
    {
        $filters = ['order_permit_department_id' => '2'];
        $service = Mockery::mock(ProjectOrderPermitService::class);
        $service->shouldReceive('list')
            ->once()
            ->with('project-1', $filters)
            ->andReturn(new Collection());

        $rows = (new ProjectOrderPermitExport($service, 'project-1', $filters))->collection();

        $this->assertCount(0, $rows);
    }

    public function test_map_uses_presenter_values_and_leaves_nulls_blank(): void
    {
        $export = new ProjectOrderPermitExport(
            Mockery::mock(ProjectOrderPermitService::class),
            'project-1',
        );

        $row = $export->map($this->workOrder());

        $this->assertSame($export->headings(), [
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
        ]);
        $this->assertCount(count($export->headings()), $row);
        $this->assertSame('WO-100', $row[0]);
        $this->assertSame('441', $row[1]);
        $this->assertSame('915', $row[2]);
        $this->assertSame('مشاريع', $row[3]);
        $this->assertSame('تعزيز شبكة ارضية ومحطات', $row[4]);
        $this->assertSame('حفريات', $row[5]);
        $this->assertEquals(40, $row[6]);
        $this->assertSame(Carbon::today()->subDays(7)->toDateString(), $row[7]);
        $this->assertSame('مقاول أ', $row[8]);
        $this->assertSame('إدارة مكة', $row[9]);
        $this->assertSame('العوالي', $row[10]);
        $this->assertSame('', $row[11]);
        $this->assertSame('', $row[12]);
        $this->assertSame(7, $row[33]);
        $this->assertSame('متاخر جدا', $row[34]);
        $this->assertSame('م. أحمد', $row[35]);
        $this->assertSame('التنفيذ', $row[36]);
        $this->assertSame('جارٍ', $row[37]);
        $this->assertSame(40.0, $row[45]);
        $this->assertSame(10, $row[46]);
        $this->assertSame(25.0, $row[47]);
        $this->assertSame('40', $row[48]);
        $this->assertSame('لا يحتاج', $row[49]);

        $empty = $export->map($this->emptyWorkOrder());

        $this->assertSame('', $empty[0]);
        $this->assertSame('', $empty[3]);
        $this->assertSame('', $empty[7]);
        $this->assertSame('', $empty[33]);
        $this->assertSame('لم ينشآ', $empty[28]);
        $this->assertSame('غير متاخر', $empty[34]);
        $this->assertSame('لا يحتاج', $empty[48]);
        $this->assertSame('لا يحتاج', $empty[49]);
    }

    private function workOrder(): ProjectOrderPermit
    {
        $department = new OrderPermitDepartment(['name' => 'مشاريع']);
        $orderPermit = new OrderPermit([
            'code' => '441',
            'type' => '915',
            'description' => 'تعزيز شبكة ارضية ومحطات',
            'uds_period' => 40,
        ]);
        $orderPermit->setRelation('department', $department);
        $orderPermit->setRelation('orderPermitType', new OrderPermitType(['name' => 'حفريات']));

        $workOrder = new ProjectOrderPermit([
            'name' => 'WO-100',
            'assigned_date' => Carbon::today()->subDays(7)->toDateString(),
            'consultant_column_155_entry_date' => Carbon::today()->subDays(10)->toDateString(),
            'target_drilling' => 10,
            'achieved_drilling' => 4,
        ]);
        $workOrder->setRelation('orderPermit', $orderPermit);
        $workOrder->setRelation('department', null);
        $workOrder->setRelation('contractor', new ProjectContractor(['name' => 'مقاول أ']));
        $workOrder->setRelation('projectManagement', new ProjectManagement(['name' => 'إدارة مكة']));
        $workOrder->setRelation('projectDistrict', new ProjectDistrict(['name' => 'العوالي']));
        $workOrder->setRelation('projectCompletionPhase', new ProjectCompletionPhase(['name' => 'التنفيذ']));
        $workOrder->setRelation('projectPhaseStatus', new ProjectPhaseStatus(['name' => 'جارٍ']));
        $workOrder->setRelation('employee', new User(['name' => 'م. أحمد']));
        $workOrder->setRelation('state', null);
        $workOrder->setRelation('connectionCompletionPhase', null);
        $workOrder->setRelation('connectionPhaseStatus', null);
        $workOrder->setRelation('noteLogs', new Collection());

        return $workOrder;
    }

    private function emptyWorkOrder(): ProjectOrderPermit
    {
        $workOrder = new ProjectOrderPermit();
        $workOrder->setRelation('orderPermit', null);
        $workOrder->setRelation('department', null);
        $workOrder->setRelation('contractor', null);
        $workOrder->setRelation('projectManagement', null);
        $workOrder->setRelation('projectDistrict', null);
        $workOrder->setRelation('projectCompletionPhase', null);
        $workOrder->setRelation('projectPhaseStatus', null);
        $workOrder->setRelation('connectionCompletionPhase', null);
        $workOrder->setRelation('connectionPhaseStatus', null);
        $workOrder->setRelation('employee', null);
        $workOrder->setRelation('state', null);
        $workOrder->setRelation('noteLogs', new Collection());

        return $workOrder;
    }
}
