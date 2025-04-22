<?php

namespace Modules\JobTitle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\JobTitle\Models\JobTitle;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class JobTitleModulesSeederTableSeeder extends Seeder
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
        $data = [
            ['en' => 'General Manager', 'ar' => 'مدير عام',"for_central_company" => true],
            ['en' => 'Head of Department', 'ar' => 'تصنيف', 'رئيس قسم',"for_central_company" => false],
            ['en' => 'hr manager', 'ar' => 'مدير الموارد البشرية',"for_central_company" => false],
        ];

        foreach ($data as $item) {
            JobTitle::Create(
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],"for_central_company" => $item['for_central_company'] ]
            );
        }

    }
}
