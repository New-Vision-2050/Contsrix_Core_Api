<?php

declare(strict_types=1);

namespace Modules\Subscription\Database\Seeders;

use Illuminate\Database\Seeder;
use Ranium\SeedOnce\Traits\SeedOnce;
use Illuminate\Database\Eloquent\Model;
use Modules\Subscription\Models\Module;

class FeatureDatabaseSeeder extends Seeder
{
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $featuresByModules = [
            'users' => [
                [
                    'name' => ['en' => 'Create User', 'ar' => 'إنشاء مستخدم'],
                    'slug' => 'create-user',
                ],
                [
                    'name' => ['en' => 'Personal Data', 'ar' => 'البيانات الشخصية'],
                    'slug' => 'personal_data',
                ],
                [
                    'name' => ['en' => 'Academic Data', 'ar' => 'البيانات الأكاديمية'],
                    'slug' => 'academic_data',
                ],
                [
                    'name' => ['en' => 'Experiences', 'ar' => 'الخبرات'],
                    'slug' => 'experiences',
                ],
                [
                    'name' => ['en' => 'Job Data', 'ar' => 'البيانات الوظيفية'],
                    'slug' => 'job_data',
                ],
                [
                    'name' => ['en' => 'Contractual Data', 'ar' => 'البيانات التعاقدية'],
                    'slug' => 'contractual_data',
                ],
                [
                    'name' => ['en' => 'Financial Privileges', 'ar' => 'الامتيازات المالية'],
                    'slug' => 'financial_privileges',
                ],
                [
                    'name' => ['en' => 'Contract Management', 'ar' => 'إدارة العقد'],
                    'slug' => 'contract_management',
                ],
            ],
            'companies' => [
                'name' => ['en' => '', 'ar' => ''],
                'slug' => '',
            ]
        ];


        foreach ($featuresByModules as $moduleSlug => $features) {
            $module = Module::where('slug', $moduleSlug)->firstOrFail('id');
            $module->features()->createMany($features);
        }
    }
}
