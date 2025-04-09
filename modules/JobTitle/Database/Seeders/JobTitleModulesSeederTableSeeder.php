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
            ['en' => 'General Manager', 'ar' => 'مدير عام'],
            ['en' => 'Head of Department', 'ar' => 'تصنيف', 'رئيس قسم'],
            ['en' => 'hr manager', 'ar' => 'مدير الموارد البشرية'],
        ];

        foreach ($data as $item) {
            JobTitle::Create(
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
