<?php

namespace Modules\Shared\TypeAllowance\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\TypeAllowance\Models\TypeAllowance;
use Ranium\SeedOnce\Traits\SeedOnce;

class TypeAllowancesSeederTableSeeder extends Seeder
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
            ['ar' => 'ثابت', 'en' => 'Constant','code'=>'constant'],
            ['ar' => 'توفير', 'en' => 'Savings','code'=>'saving'],
        ];

        foreach ($typeAllowances as $index => $item) {
            TypeAllowance::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                    'code' => $item['code']
                ]
            );
        }
    }
}
