<?php

namespace Modules\Shared\ProfessionalBodie\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\ProfessionalBodie\Models\ProfessionalBodie;
use Ranium\SeedOnce\Traits\SeedOnce;

class ProfessionalBodiesSeeder extends Seeder
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


        $specializations = [
            ['ar' => 'الهيئة السعودية للمهندسين', 'en' => 'Saudi Council of Engineers','code'=>'engineering'],
            ['ar' => 'نقابة المهندسين', 'en' => 'Engineers Syndicate','code'=>'engineering'],
        ];

        foreach ($specializations as $index => $item) {
            ProfessionalBodie::firstOrCreate(
                ['id' => $index + 1],
                ['name' => ['en' => $item['en'], 'ar' => $item['ar']],'code'=>$item['code']]
            );
        }
    }

}
