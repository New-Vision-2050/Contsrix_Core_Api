<?php

namespace Modules\Shared\TypeAllowance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Ranium\SeedOnce\Traits\SeedOnce;

class TypeAllowanceSeederTableSeeder extends Seeder
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
            ['ar' => 'نسبة', 'en' => 'Ratio','type'=>'ratio'],
            ['ar' => 'ثابت', 'en' => 'Constant','type'=>'constant'],
            ['ar' => 'توفير', 'en' => 'Savings','type'=>'saving'],
        ];

        foreach ($typeAllowances as $index => $item) {
            TypeAllowance::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'type' => $item['type']
                ]
            );
        }
    }
}
