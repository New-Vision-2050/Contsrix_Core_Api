<?php

namespace Modules\Shared\RightTerminate\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\SalaryType\Models\SalaryType;
use Modules\Shared\RightTerminate\Models\RightTerminate;
use Ranium\SeedOnce\Traits\SeedOnce;

class RightTerminateSeederTableSeeder extends Seeder
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
            ['ar' => 'كلا الطرفين', 'en' => 'Both parties'],
            ['ar' => 'طرف واحد', 'en' => 'One party'],
        ];

        foreach ($typeAllowances as $index => $item) {
            RightTerminate::create(
                [
                    'name' => ['en' => $item['en'], 'ar' => $item['ar']],
                ]
            );
        }
    }
}
