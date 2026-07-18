<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectType\Models\OrderPermit;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\OrderPermitType;

class OrderPermitSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $departmentsData = [
                ['name' => 'توصيلات'],
                ['name' => 'مشاريع'],
                ['name' => 'عمليات'],
            ];

            $departmentMap = [];

            foreach ($departmentsData as $deptData) {
                $department = OrderPermitDepartment::firstOrCreate(
                    ['name' => $deptData['name']],
                    ['name' => $deptData['name']]
                );

                $departmentMap[$deptData['name']] = $department->id;
            }

            $typesData = [
                ['name' => 'عدادات'],
                ['name' => 'حفريات'],
                ['name' => 'مشاريع'],
                ['name' => 'عمليات'],
                ['name' => 'توصيلات'],
            ];

            $typeMap = [];

            foreach ($typesData as $typeData) {
                $type = OrderPermitType::firstOrCreate(
                    ['name' => $typeData['name']],
                    ['name' => $typeData['name']]
                );

                $typeMap[$typeData['name']] = $type->id;
            }

            $orderPermits = [
                ['code' => '401', 'description' => 'توصيل عداد بدون حفرية', 'type' => '912', 'uds_period' => 5, 'department' => 'توصيلات', 'permit_type' => 'عدادات'],
                ['code' => '402', 'description' => 'توصيل عداد بحفرية شبكة أرضية', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '403', 'description' => 'توصيل عداد شبكة هوائية LV', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'عدادات'],
                ['code' => '404', 'description' => 'توصيل عداد بمحطة شب', 'type' => '913', 'uds_period' => 30, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '405', 'description' => 'توصيل عداد بمحول شبكة هوائية', 'type' => '913', 'uds_period' => 30, 'department' => 'توصيلات', 'permit_type' => 'عدادات'],
                ['code' => '406', 'description' => 'تغذية صغيرة بدون عداد', 'type' => '913', 'uds_period' => 5, 'department' => 'توصيلات', 'permit_type' => 'عدادات'],
                ['code' => '407', 'description' => 'ايصال مؤقت مناسبات', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '408', 'description' => 'ازالة عداد علي المشترك', 'type' => '913', 'uds_period' => 5, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '409', 'description' => 'ازالة-نقل شبكة علي المشترك', 'type' => '917', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '410', 'description' => 'انشاء محطة / محول لمشترك / مشتركين', 'type' => '913', 'uds_period' => 30, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '430', 'description' => 'مخططات منح وزارة البلدية', 'type' => '920', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '432', 'description' => 'أتتمتة الشبكة', 'type' => '919', 'uds_period' => 30, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '441', 'description' => 'تعزيز شبكة ارضية ومحطات', 'type' => '915', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '442', 'description' => 'تعزيز شبكة هوائية ومحطات', 'type' => '915', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '450', 'description' => 'مشاريع ربط محطات التحويل', 'type' => '916', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '460', 'description' => 'استبدال شبكات', 'type' => '914', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '465', 'description' => 'استبدال شبكات التوزيع', 'type' => '914', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '490', 'description' => 'كهربة مخططات منح وزارة الاسكان', 'type' => '918', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '491', 'description' => 'كهربة المخططات الخاصة', 'type' => '925', 'uds_period' => 40, 'department' => 'عمليات', 'permit_type' => 'عمليات'],
                ['code' => '801', 'description' => 'تركيب عداد ايصال سريع', 'type' => '912', 'uds_period' => 7, 'department' => 'توصيلات', 'permit_type' => 'عدادات'],
                ['code' => '802', 'description' => 'تمديد شبكة ارضية LV ايصال سريع', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'توصيلات'],
                ['code' => '803', 'description' => 'تمديد شبكة هوائية LV ايصال سريع', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '804', 'description' => 'تركيب محطة ش ارضية MV ايصال سريع', 'type' => '913', 'uds_period' => 30, 'department' => 'توصيلات', 'permit_type' => 'حفريات'],
                ['code' => '805', 'description' => 'تركيب محطة ش هوائية MV ايصال سريع', 'type' => '913', 'uds_period' => 30, 'department' => 'توصيلات', 'permit_type' => 'توصيلات'],
                ['code' => '806', 'description' => 'ايصال وزارة الاسكان جهد منخفض', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'توصيلات'],
                ['code' => '810', 'description' => 'تنفيذ اعمال علي حساب المشترك', 'type' => '913', 'uds_period' => 20, 'department' => 'توصيلات', 'permit_type' => 'توصيلات'],
                ['code' => '466', 'description' => 'اتمتتة شبكات ارضى', 'type' => '914', 'uds_period' => 30, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '616', 'description' => 'استبدال عدادات', 'type' => '925', 'uds_period' => 20, 'department' => 'عمليات', 'permit_type' => 'عمليات'],
                ['code' => '444', 'description' => 'تحويل الشبكة من هوائي الى أرضي', 'type' => '915', 'uds_period' => 30, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '901', 'description' => 'نوصيلات طاقة شمسية', 'type' => '912', 'uds_period' => 7, 'department' => 'توصيلات', 'permit_type' => 'توصيلات'],
                ['code' => '660', 'description' => 'الحرم المكي و المشاعر', 'type' => '915', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '617', 'description' => 'تهذيب عدادات 2024', 'type' => '925', 'uds_period' => 20, 'department' => 'عمليات', 'permit_type' => 'عمليات'],
                ['code' => '467', 'description' => 'اتمتة شبكات هوائي', 'type' => '914', 'uds_period' => 30, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '434', 'description' => 'مشروع الاتمتة 2025', 'type' => '919', 'uds_period' => 30, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '334', 'description' => 'أتمتة معدات', 'type' => '919', 'uds_period' => 20, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '360', 'description' => 'احلال', 'type' => '914', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '502', 'description' => 'استبدال عداد (صيانه)', 'type' => '911', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '342', 'description' => 'استبدال شبكات', 'type' => '914', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '344', 'description' => 'تحويل الشبكة من هوائي الى أرضي', 'type' => '915', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
                ['code' => '341', 'description' => 'تعزيز شبكة ارضيه ومحطات', 'type' => '915', 'uds_period' => 40, 'department' => 'مشاريع', 'permit_type' => 'مشاريع'],
            ];

            $createdCount = 0;

            foreach ($orderPermits as $data) {
                $orderPermit = OrderPermit::firstOrCreate(
                    [
                        'code' => $data['code'],
                        'description' => $data['description'],
                    ],
                    [
                        'type' => $data['type'],
                        'uds_period' => $data['uds_period'],
                        'order_permit_department_id' => $departmentMap[$data['department']] ?? null,
                        'order_permit_type_id' => $typeMap[$data['permit_type']] ?? null,
                    ]
                );

                if ($orderPermit->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            $this->command->info('OrderPermit seeder completed successfully!');
            $this->command->info("Created {$createdCount} order permits, " . count($departmentMap) . " departments, and " . count($typeMap) . " types.");
        });
    }
}
