<?php

namespace Modules\Shared\TimeUnit\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\TimeUnit\Models\TimeUnit;
use Ranium\SeedOnce\Traits\SeedOnce;

class TimeUnitSeederTableSeeder extends Seeder
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
            ['ar' => 'يوم', 'en' => 'day'],
            ['ar' => 'شهر', 'en' => 'month'],
            ['ar' => 'سنه', 'en' => 'year'],
        ];

        foreach ($typeAllowances as $index => $item) {
            TimeUnit::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                ]
            );
        }
    }
}
