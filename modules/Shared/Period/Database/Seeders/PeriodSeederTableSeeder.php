<?php

namespace Modules\Shared\Period\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\AcademicQualification\Models\AcademicQualification;
use Modules\Shared\Period\Models\Period;
use Ranium\SeedOnce\Traits\SeedOnce;

class PeriodSeederTableSeeder extends Seeder
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

        $periods = [
            ['ar' => 'شهري', 'en' => 'Monthly'],
            ['ar' => 'سنوي', 'en' => 'Annually'],
        ];

        foreach ($periods as $index => $item) {
            Period::firstOrCreate(
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
