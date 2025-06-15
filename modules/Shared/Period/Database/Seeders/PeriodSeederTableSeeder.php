<?php

namespace Modules\Shared\Period\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Period\Models\Period;
use Ranium\SeedOnce\Traits\SeedOnce;

class PeriodSeederTableSeeder extends Seeder
{
<<<<<<< HEAD
=======
    use SeedOnce;
>>>>>>> 7be6c72c (merge with stage (first version ))
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
<<<<<<< HEAD
        $periods = [
            ['ar' => 'شهري', 'en' => 'Monthly'],
            ['ar' => 'سنوي', 'en' => 'Annually'],
            ['ar' => 'يومي', 'en' => 'Daily']
        ];

        foreach ($periods as $item) {
            Period::firstOrCreate(
                ['name->en' => $item['en']],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }

=======

        $periods = [
            ['ar' => 'شهري', 'en' => 'Monthly'],
            ['ar' => 'سنوي', 'en' => 'Annually'],
        ];

        foreach ($periods as $index => $item) {
            Period::create(
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']]]
            );
        }
>>>>>>> 7be6c72c (merge with stage (first version ))
    }
}
