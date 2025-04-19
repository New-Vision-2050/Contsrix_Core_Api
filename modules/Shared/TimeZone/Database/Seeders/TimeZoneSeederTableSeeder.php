<?php

namespace Modules\Shared\TimeZone\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\TimeZone\Models\TimeZone;
use Ranium\SeedOnce\Traits\SeedOnce;

class TimeZoneSeederTableSeeder extends Seeder
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
        TimeZone::create([
            'country_id'=> 64,
            'time_zone' => 'GMT+2'
        ]);

        TimeZone::create([
            'country_id'=> 191,
            'time_zone' => 'GMT+3'
        ]);

    }

}
