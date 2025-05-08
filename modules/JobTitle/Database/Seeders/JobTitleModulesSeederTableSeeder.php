<?php

namespace Modules\JobTitle\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Company\CompanyCore\Models\Company;
use Modules\JobTitle\Models\JobTitle;
use Ranium\SeedOnce\Traits\SeedOnce;
use Ramsey\Uuid\Uuid;

class JobTitleModulesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $data = [

            ['en' => 'General Manager', 'ar' => 'مدير عام','type'=>'general_manager'],
            ['en' => 'Head of Department', 'ar' => 'تصنيف', 'رئيس قسم','type'=>'head_department'],
            ['en' => 'hr manager', 'ar' => 'مدير الموارد البشرية','type'=>'hr_manager'],
        ];

        foreach ($data as $item) {
            JobTitle::Create(

                ['name' => ['en' => $item['en'], 'ar' => $item['ar']] ,'type'=> $item['type'],"company_id"=>tenant("id")??Company::query()->first()->id]
            );
        }

    }
}
