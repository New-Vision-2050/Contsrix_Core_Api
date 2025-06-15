<?php

namespace Modules\Shared\TypeWorkingHour\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\TypeWorkingHour\Models\TypeWorkingHour;
use Ranium\SeedOnce\Traits\SeedOnce;

class TypeWorkingHourSeederTableSeeder extends Seeder
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

        $typeAllowances = [
            ['ar' => 'يومي', 'en' => 'Daily'],
            ['ar' => 'اسبوعي', 'en' => 'Weekly'],
            ['ar' => 'شهري', 'en' => 'Monthly'],
        ];

        foreach ($typeAllowances as $index => $item) {
            TypeWorkingHour::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                ]
            );
        }
    }
}
