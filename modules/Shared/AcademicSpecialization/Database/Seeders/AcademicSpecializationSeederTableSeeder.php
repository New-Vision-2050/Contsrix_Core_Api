<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce;

class AcademicSpecializationSeederTableSeeder extends Seeder
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

        $specializations = [
            ['ar' => 'علوم الحاسوب', 'en' => 'Computer Science'],
            ['ar' => 'هندسة كهربائية', 'en' => 'Electrical Engineering'],
            ['ar' => 'هندسة مدنية', 'en' => 'Civil Engineering'],
            ['ar' => 'طب بشري', 'en' => 'Medicine'],
            ['ar' => 'صيدلة', 'en' => 'Pharmacy'],
            ['ar' => 'إدارة أعمال', 'en' => 'Business Administration'],
            ['ar' => 'محاسبة', 'en' => 'Accounting'],
            ['ar' => 'لغة إنجليزية', 'en' => 'English Language'],
        ];

        foreach ($specializations as $index => $item) {
            AcademicSpecialization::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
