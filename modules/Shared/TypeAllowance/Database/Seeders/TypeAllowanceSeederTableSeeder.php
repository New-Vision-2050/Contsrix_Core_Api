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
            ['ar' => 'نسبة', 'en' => 'Ratio'],
            ['ar' => 'ثابت', 'en' => 'Constant'],
            ['ar' => 'توفير', 'en' => 'Savings'],
        ];

        foreach ($typeAllowances as $index => $item) {
            TypeAllowance::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
    }
}
