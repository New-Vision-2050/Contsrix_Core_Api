<?php

namespace Modules\Shared\MaritalStatus\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\MaritalStatus\Models\MaritalStatus;
use Ranium\SeedOnce\Traits\SeedOnce;

class MaritalStatusSeederTableSeeder extends Seeder
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

        $data = [
            ["name"=>['ar' => 'متزوج', 'en' => 'Married'],"type"=>"married"],
            ["name"=>['ar' => 'غير متزوج', 'en' => 'Single'],"type"=>"not-married"],
//            ['ar' => 'متزوج ويعول', 'en' => 'Married with children'],
//            ['ar' => 'مطلق', 'en' => 'Divorced'],
//            ['ar' => 'أرمل', 'en' => 'Widowed'],
//            ['ar' => 'مخطوب', 'en' => 'Engaged'],
//            ['ar' => 'منفصل', 'en' => 'Separated'],
//            ['ar' => 'مطلق ويعول', 'en' => 'Divorced with children'],
        ];

        foreach ($data as $index => $item) {
            MaritalStatus::create(
                $item
            );
        }
    }
}
