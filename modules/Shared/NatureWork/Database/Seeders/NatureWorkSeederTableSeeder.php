<?php

namespace Modules\Shared\NatureWork\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\NatureWork\Models\NatureWork;
use Ranium\SeedOnce\Traits\SeedOnce;

class NatureWorkSeederTableSeeder extends Seeder
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
            ['ar' => 'عقد بدوام كامل', 'en' => 'Full-time contract'],
            ['ar' => 'عقد بدوام جزئي', 'en' => 'part-time contract'],
        ];

        foreach ($typeAllowances as $index => $item) {
            NatureWork::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                ]
            );
        }
    }
}
