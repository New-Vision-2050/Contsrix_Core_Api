<?php

namespace Modules\Shared\SalaryType\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Ranium\SeedOnce\Traits\SeedOnce;

class SalaryTypeSeederTableSeeder extends Seeder
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
            ['ar' => 'نسبة', 'en' => 'Ratio','code'=>'percentage'],
            ['ar' => 'ثابت', 'en' => 'Constant','code'=>'constant'],
        ];

        foreach ($typeAllowances as $index => $item) {
            SalaryType::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'code' => $item['code']
                ]
            );
        }
    }
}
