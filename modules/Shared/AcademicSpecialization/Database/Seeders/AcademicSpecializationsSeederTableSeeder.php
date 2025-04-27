<?php

namespace Modules\Shared\AcademicSpecialization\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicSpecialization\Models\AcademicSpecialization;
use Ranium\SeedOnce\Traits\SeedOnce;

class AcademicSpecializationsSeederTableSeeder extends Seeder
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

        $specializations =
        [
            ['ar' => 'علوم الحاسوب', 'en' => 'Computer Science', 'code' => 'science'],
            ['ar' => 'هندسة كهربائية', 'en' => 'Electrical Engineering', 'code' => 'engineering'],
            ['ar' => 'هندسة مدنية', 'en' => 'Civil Engineering', 'code' => 'engineering'],
            ['ar' => 'طب بشري', 'en' => 'Medicine', 'code' => 'medicine'],
            ['ar' => 'صيدلة', 'en' => 'Pharmacy', 'code' => 'medicine'],
            ['ar' => 'إدارة أعمال', 'en' => 'Business Administration', 'code' => 'business'],
            ['ar' => 'محاسبة', 'en' => 'Accounting', 'code' => 'business'],
            ['ar' => 'لغة إنجليزية', 'en' => 'English Language', 'code' => 'arts'],
        ];

        foreach ($specializations as $index => $item) {
            AcademicSpecialization::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],'code'=>$item['code']]
            );
        }
    }
}
