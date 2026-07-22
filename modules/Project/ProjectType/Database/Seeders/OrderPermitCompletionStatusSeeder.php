<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectType\Models\ConnectionCompletionPhase;
use Modules\Project\ProjectType\Models\ConnectionPhaseStatus;
use Modules\Project\ProjectType\Models\OrderPermitDepartment;
use Modules\Project\ProjectType\Models\ProjectCompletionPhase;
use Modules\Project\ProjectType\Models\ProjectPhaseStatus;

class OrderPermitCompletionStatusSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $projectDept = OrderPermitDepartment::where('name', 'مشاريع')->first();
            $connectionDept = OrderPermitDepartment::where('name', 'توصيلات')->first();

            if (! $projectDept) {
                $this->command?->error("OrderPermitDepartment 'مشاريع' not found. Run OrderPermitSeeder first.");
                return;
            }

            if (! $connectionDept) {
                $this->command?->error("OrderPermitDepartment 'توصيلات' not found. Run OrderPermitSeeder first.");
                return;
            }

            $phases = [
                'التصاريح' => [
                    'لم ينشأ',
                    'مسودة تصريح',
                    'قيد التنسيق',
                    'انتظار السداد',
                    'ملغي لعدم السداد',
                    'تم الاصدار',
                    'لا يحتاج',
                    'انتهاء التنسيق رفض',
                ],
                'التنفيذ' => [
                    'حفر وتمديد',
                    'تركيب/استبدال معدات (لا يحتاج حفر)',
                    'عوائق تنفيذ',
                    'تم التنفيذ',
                ],
                'مرحلة التشغيل' => [
                    'طلب الكلير',
                    'قيم الحماية',
                    'اختبارات ما قبل التشغيل',
                    'مرسل برنامج/حوكمة',
                    'عوائق تشغيل',
                    'تشغيل جزئي',
                    'تم التشغيل',
                ],
                'مرحلة الإغلاق' => [
                    'ارفاق مستندات',
                    'تعديل المقايسة',
                    'الاعتماد الورقي',
                    'الاعتماد الآلي',
                    'صرف ورجيع وتخريد',
                    'قبول الشهادة',
                    'تم الانتهاء',
                ],
                'تحت المعالجة' => [
                    'تعديل اسناد',
                    'مغلق بدون تنفيذ',
                    'استلام أصول',
                    'مشكلة حاسب',
                    'منتهي تعديل اسناد',
                ],
            ];

            $this->seedProjectPhases($phases, $projectDept->id);
            $this->seedConnectionPhases($phases, $connectionDept->id);

            $this->command?->info('OrderPermitCompletionStatus seeder completed successfully!');
        });
    }

    private function seedProjectPhases(array $phases, int $departmentId): void
    {
        foreach ($phases as $phaseName => $statuses) {
            $phase = ProjectCompletionPhase::firstOrCreate(
                [
                    'order_permit_department_id' => $departmentId,
                    'name' => $phaseName,
                ],
                [
                    'order_permit_department_id' => $departmentId,
                    'name' => $phaseName,
                ]
            );

            foreach ($statuses as $statusName) {
                ProjectPhaseStatus::firstOrCreate(
                    [
                        'project_completion_phase_id' => $phase->id,
                        'name' => $statusName,
                    ],
                    [
                        'project_completion_phase_id' => $phase->id,
                        'name' => $statusName,
                    ]
                );
            }
        }
    }

    private function seedConnectionPhases(array $phases, int $departmentId): void
    {
        foreach ($phases as $phaseName => $statuses) {
            $phase = ConnectionCompletionPhase::firstOrCreate(
                [
                    'order_permit_department_id' => $departmentId,
                    'name' => $phaseName,
                ],
                [
                    'order_permit_department_id' => $departmentId,
                    'name' => $phaseName,
                ]
            );

            foreach ($statuses as $statusName) {
                ConnectionPhaseStatus::firstOrCreate(
                    [
                        'connection_completion_phase_id' => $phase->id,
                        'name' => $statusName,
                    ],
                    [
                        'connection_completion_phase_id' => $phase->id,
                        'name' => $statusName,
                    ]
                );
            }
        }
    }
}
