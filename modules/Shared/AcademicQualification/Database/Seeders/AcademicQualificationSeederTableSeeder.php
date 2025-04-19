<?php

namespace Modules\Shared\AcademicQualification\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Ranium\SeedOnce\Traits\SeedOnce;

class AcademicQualificationSeederTableSeeder extends Seeder
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

        $qualifications = [
            ['ar' => 'ماجستير', 'en' => 'Master'],
            ['ar' => 'بكالوريوس', 'en' => 'Bachelor'],
            ['ar' => 'ليسانس', 'en' => 'Licentiate'],
            ['ar' => 'الثانوية العامة', 'en' => 'High School'],
            ['ar' => 'دبلوم تجاري', 'en' => 'Commercial Diploma'],
            ['ar' => 'دبلوم صناعي', 'en' => 'Industrial Diploma'],
            ['ar' => 'دبلوم زراعي', 'en' => 'Agricultural Diploma'],
        ];

        foreach ($qualifications as $index => $item) {
            AcademicQualification::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
