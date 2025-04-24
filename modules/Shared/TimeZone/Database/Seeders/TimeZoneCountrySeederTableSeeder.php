<?php

namespace Modules\Shared\TimeZone\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Country\Models\Country;
use Modules\Shared\TimeZone\Models\TimeZone;
use Ranium\SeedOnce\Traits\SeedOnce;

class TimeZoneCountrySeederTableSeeder extends Seeder
{
    use SeedOnce;

    public function run()
    {
        Model::unguard();

        $countries = Country::get();

        foreach ($countries as $country) {
            // make sure time_zone exists and is array
                foreach ($country['timezones'] as $tz) {
                    TimeZone::create([
                        'country_id' => $country['id'],
                        'zone_name' => $tz['zoneName'] ?? '',
                        'gmt_offset' => $tz['gmtOffset'] ?? 0,
                        'gmt_offset_name' => $tz['gmtOffsetName'] ?? '',
                        'abbreviation' => $tz['abbreviation'] ?? '',
                        'tz_name' => $tz['tzName'] ?? '',
                    ]);
                }
            }
    }
}

