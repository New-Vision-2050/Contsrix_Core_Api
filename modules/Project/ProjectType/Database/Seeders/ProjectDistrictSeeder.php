<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Project\ProjectType\Models\ProjectDistrict;

class ProjectDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $projectId = '606e9811-0983-4a62-8128-1590fb73a397';

        $districts = [
            'الموقع',
            'مخطط زهرة العمرة',
            'بطحاء قريش',
            'ام الكتاد',
            'مخطط الامير فواز',
            'الراشدية 1',
            'بحره',
            'شرائع المجاهدين',
            'العسيله',
            'العمره',
            'الجموم',
            'الزايدي',
            'ولى العهد',
            'خليص',
            'البحيرات',
            'العوالي',
            'ابو مراغ',
            'الشرائع',
            'النوارية',
            'العزيزية',
            'الهدى',
            'السلولي',
            'الاطوي',
            'مني',
            'محبس الجن',
            'المسفلة',
            'الحسينية',
            'الرصيفه',
            'الهجره',
            'الشوقية',
            'ملكان',
            'النزهة',
            'المحمديه',
            'البريد المركزي',
            'جعرانة',
            'جبل السيدة',
            'الصفوه',
            'الاسكان',
            'الوسيق',
            'المعيصم',
            'الخالدية',
            'النسيم',
            'السبهاني',
            'عرفات',
            'كدى',
            'الصفوة',
            'ام دوحة',
            'التشليح',
            'دفاق',
            'نعمان',
            'ام الجود',
            'القشلة',
            'الزاهر',
            'الخضراء',
            'الشافعي',
        ];

        DB::transaction(function () use ($projectId, $districts) {
            $createdCount = 0;

            foreach ($districts as $name) {
                $district = ProjectDistrict::firstOrCreate(
                    [
                        'project_id' => $projectId,
                        'name'       => $name,
                    ],
                    [
                        'project_id' => $projectId,
                        'name'       => $name,
                    ]
                );

                if ($district->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            $this->command->info('ProjectDistrict seeder completed successfully!');
            $this->command->info("Created {$createdCount} project districts for project {$projectId}.");
        });
    }
}
