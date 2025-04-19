<?php

namespace Modules\Shared\TimeZone\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Modules\Country\Models\Country;
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
        // Get all countries to reference by ISO code
        $countries = Country::all()->keyBy('iso2');

        // Array of timezones with their corresponding country ISO codes
        $timezones = [
            // Africa
            ['country_code' => 'DZ', 'time_zone' => 'Africa/Algiers'], // Algeria
            ['country_code' => 'EG', 'time_zone' => 'Africa/Cairo'], // Egypt
            ['country_code' => 'MA', 'time_zone' => 'Africa/Casablanca'], // Morocco
            ['country_code' => 'NG', 'time_zone' => 'Africa/Lagos'], // Nigeria
            ['country_code' => 'ZA', 'time_zone' => 'Africa/Johannesburg'], // South Africa
            ['country_code' => 'TN', 'time_zone' => 'Africa/Tunis'], // Tunisia
            ['country_code' => 'KE', 'time_zone' => 'Africa/Nairobi'], // Kenya
            ['country_code' => 'GH', 'time_zone' => 'Africa/Accra'], // Ghana
            ['country_code' => 'ET', 'time_zone' => 'Africa/Addis_Ababa'], // Ethiopia
            ['country_code' => 'SD', 'time_zone' => 'Africa/Khartoum'], // Sudan

            // America
            ['country_code' => 'AR', 'time_zone' => 'America/Argentina/Buenos_Aires'], // Argentina
            ['country_code' => 'BR', 'time_zone' => 'America/Sao_Paulo'], // Brazil
            ['country_code' => 'CA', 'time_zone' => 'America/Toronto'], // Canada
            ['country_code' => 'CA', 'time_zone' => 'America/Vancouver'], // Canada
            ['country_code' => 'CA', 'time_zone' => 'America/Edmonton'], // Canada
            ['country_code' => 'CA', 'time_zone' => 'America/Halifax'], // Canada
            ['country_code' => 'CL', 'time_zone' => 'America/Santiago'], // Chile
            ['country_code' => 'CO', 'time_zone' => 'America/Bogota'], // Colombia
            ['country_code' => 'MX', 'time_zone' => 'America/Mexico_City'], // Mexico
            ['country_code' => 'PE', 'time_zone' => 'America/Lima'], // Peru
            ['country_code' => 'US', 'time_zone' => 'America/New_York'], // USA (Eastern)
            ['country_code' => 'US', 'time_zone' => 'America/Chicago'], // USA (Central)
            ['country_code' => 'US', 'time_zone' => 'America/Denver'], // USA (Mountain)
            ['country_code' => 'US', 'time_zone' => 'America/Los_Angeles'], // USA (Pacific)
            ['country_code' => 'US', 'time_zone' => 'America/Anchorage'], // USA (Alaska)
            ['country_code' => 'US', 'time_zone' => 'Pacific/Honolulu'], // USA (Hawaii)
            ['country_code' => 'VE', 'time_zone' => 'America/Caracas'], // Venezuela
            ['country_code' => 'UY', 'time_zone' => 'America/Montevideo'], // Uruguay
            ['country_code' => 'PA', 'time_zone' => 'America/Panama'], // Panama

            // Asia
            ['country_code' => 'AZ', 'time_zone' => 'Asia/Baku'], // Azerbaijan
            ['country_code' => 'BD', 'time_zone' => 'Asia/Dhaka'], // Bangladesh
            ['country_code' => 'CN', 'time_zone' => 'Asia/Shanghai'], // China
            ['country_code' => 'CN', 'time_zone' => 'Asia/Urumqi'], // China
            ['country_code' => 'HK', 'time_zone' => 'Asia/Hong_Kong'], // Hong Kong
            ['country_code' => 'IN', 'time_zone' => 'Asia/Kolkata'], // India
            ['country_code' => 'ID', 'time_zone' => 'Asia/Jakarta'], // Indonesia
            ['country_code' => 'ID', 'time_zone' => 'Asia/Makassar'], // Indonesia
            ['country_code' => 'ID', 'time_zone' => 'Asia/Jayapura'], // Indonesia
            ['country_code' => 'IR', 'time_zone' => 'Asia/Tehran'], // Iran
            ['country_code' => 'IQ', 'time_zone' => 'Asia/Baghdad'], // Iraq
            ['country_code' => 'JP', 'time_zone' => 'Asia/Tokyo'], // Japan
            ['country_code' => 'KR', 'time_zone' => 'Asia/Seoul'], // South Korea
            ['country_code' => 'MY', 'time_zone' => 'Asia/Kuala_Lumpur'], // Malaysia
            ['country_code' => 'PK', 'time_zone' => 'Asia/Karachi'], // Pakistan
            ['country_code' => 'PH', 'time_zone' => 'Asia/Manila'], // Philippines
            ['country_code' => 'SA', 'time_zone' => 'Asia/Riyadh'], // Saudi Arabia
            ['country_code' => 'SG', 'time_zone' => 'Asia/Singapore'], // Singapore
            ['country_code' => 'TH', 'time_zone' => 'Asia/Bangkok'], // Thailand
            ['country_code' => 'TR', 'time_zone' => 'Asia/Istanbul'], // Turkey
            ['country_code' => 'AE', 'time_zone' => 'Asia/Dubai'], // UAE
            ['country_code' => 'VN', 'time_zone' => 'Asia/Ho_Chi_Minh'], // Vietnam
            ['country_code' => 'LB', 'time_zone' => 'Asia/Beirut'], // Lebanon
            ['country_code' => 'KZ', 'time_zone' => 'Asia/Almaty'], // Kazakhstan

            // Europe
            ['country_code' => 'AT', 'time_zone' => 'Europe/Vienna'], // Austria
            ['country_code' => 'BE', 'time_zone' => 'Europe/Brussels'], // Belgium
            ['country_code' => 'BG', 'time_zone' => 'Europe/Sofia'], // Bulgaria
            ['country_code' => 'HR', 'time_zone' => 'Europe/Zagreb'], // Croatia
            ['country_code' => 'CZ', 'time_zone' => 'Europe/Prague'], // Czech Republic
            ['country_code' => 'DK', 'time_zone' => 'Europe/Copenhagen'], // Denmark
            ['country_code' => 'FI', 'time_zone' => 'Europe/Helsinki'], // Finland
            ['country_code' => 'FR', 'time_zone' => 'Europe/Paris'], // France
            ['country_code' => 'DE', 'time_zone' => 'Europe/Berlin'], // Germany
            ['country_code' => 'GR', 'time_zone' => 'Europe/Athens'], // Greece
            ['country_code' => 'HU', 'time_zone' => 'Europe/Budapest'], // Hungary
            ['country_code' => 'IE', 'time_zone' => 'Europe/Dublin'], // Ireland
            ['country_code' => 'IT', 'time_zone' => 'Europe/Rome'], // Italy
            ['country_code' => 'NL', 'time_zone' => 'Europe/Amsterdam'], // Netherlands
            ['country_code' => 'NO', 'time_zone' => 'Europe/Oslo'], // Norway
            ['country_code' => 'PL', 'time_zone' => 'Europe/Warsaw'], // Poland
            ['country_code' => 'PT', 'time_zone' => 'Europe/Lisbon'], // Portugal
            ['country_code' => 'RO', 'time_zone' => 'Europe/Bucharest'], // Romania
            ['country_code' => 'RU', 'time_zone' => 'Europe/Moscow'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Europe/Kaliningrad'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Europe/Samara'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Yekaterinburg'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Omsk'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Krasnoyarsk'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Irkutsk'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Yakutsk'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Vladivostok'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Magadan'], // Russia
            ['country_code' => 'RU', 'time_zone' => 'Asia/Kamchatka'], // Russia
            ['country_code' => 'ES', 'time_zone' => 'Europe/Madrid'], // Spain
            ['country_code' => 'SE', 'time_zone' => 'Europe/Stockholm'], // Sweden
            ['country_code' => 'CH', 'time_zone' => 'Europe/Zurich'], // Switzerland
            ['country_code' => 'UA', 'time_zone' => 'Europe/Kiev'], // Ukraine
            ['country_code' => 'GB', 'time_zone' => 'Europe/London'], // United Kingdom

            // Oceania
            ['country_code' => 'AU', 'time_zone' => 'Australia/Sydney'], // Australia (Eastern)
            ['country_code' => 'AU', 'time_zone' => 'Australia/Melbourne'], // Australia (Eastern)
            ['country_code' => 'AU', 'time_zone' => 'Australia/Brisbane'], // Australia (Eastern)
            ['country_code' => 'AU', 'time_zone' => 'Australia/Adelaide'], // Australia (Central)
            ['country_code' => 'AU', 'time_zone' => 'Australia/Darwin'], // Australia (Central)
            ['country_code' => 'AU', 'time_zone' => 'Australia/Perth'], // Australia (Western)
            ['country_code' => 'NZ', 'time_zone' => 'Pacific/Auckland'], // New Zealand
            ['country_code' => 'FJ', 'time_zone' => 'Pacific/Fiji'], // Fiji

            // Middle East
            ['country_code' => 'BH', 'time_zone' => 'Asia/Bahrain'], // Bahrain
            ['country_code' => 'EG', 'time_zone' => 'Africa/Cairo'], // Egypt (GMT+2)
            ['country_code' => 'IL', 'time_zone' => 'Asia/Jerusalem'], // Israel
            ['country_code' => 'JO', 'time_zone' => 'Asia/Amman'], // Jordan
            ['country_code' => 'KW', 'time_zone' => 'Asia/Kuwait'], // Kuwait
            ['country_code' => 'LB', 'time_zone' => 'Asia/Beirut'], // Lebanon
            ['country_code' => 'OM', 'time_zone' => 'Asia/Muscat'], // Oman
            ['country_code' => 'QA', 'time_zone' => 'Asia/Qatar'], // Qatar
            ['country_code' => 'SA', 'time_zone' => 'Asia/Riyadh'], // Saudi Arabia
            ['country_code' => 'SY', 'time_zone' => 'Asia/Damascus'], // Syria
            ['country_code' => 'AE', 'time_zone' => 'Asia/Dubai'], // UAE
            ['country_code' => 'YE', 'time_zone' => 'Asia/Aden'], // Yemen

            // Generic GMT timezones
            ['country_code' => 'US', 'time_zone' => 'GMT-12:00'], // Baker Island, Howland Island
            ['country_code' => 'US', 'time_zone' => 'GMT-11:00'], // American Samoa
            ['country_code' => 'US', 'time_zone' => 'GMT-10:00'], // Hawaii
            ['country_code' => 'US', 'time_zone' => 'GMT-09:00'], // Alaska
            ['country_code' => 'US', 'time_zone' => 'GMT-08:00'], // Pacific Time
            ['country_code' => 'US', 'time_zone' => 'GMT-07:00'], // Mountain Time
            ['country_code' => 'US', 'time_zone' => 'GMT-06:00'], // Central Time
            ['country_code' => 'US', 'time_zone' => 'GMT-05:00'], // Eastern Time
            ['country_code' => 'CA', 'time_zone' => 'GMT-04:00'], // Atlantic Time
            ['country_code' => 'GL', 'time_zone' => 'GMT-03:00'], // Greenland
            ['country_code' => 'PT', 'time_zone' => 'GMT-01:00'], // Azores
            ['country_code' => 'GB', 'time_zone' => 'GMT+00:00'], // Greenwich Mean Time
            ['country_code' => 'DE', 'time_zone' => 'GMT+01:00'], // Central European Time
            ['country_code' => 'EG', 'time_zone' => 'GMT+02:00'], // Eastern European Time
            ['country_code' => 'SA', 'time_zone' => 'GMT+03:00'], // Arabia Standard Time
            ['country_code' => 'AE', 'time_zone' => 'GMT+04:00'], // Gulf Standard Time
            ['country_code' => 'PK', 'time_zone' => 'GMT+05:00'], // Pakistan Standard Time
            ['country_code' => 'BD', 'time_zone' => 'GMT+06:00'], // Bangladesh Standard Time
            ['country_code' => 'TH', 'time_zone' => 'GMT+07:00'], // Indochina Time
            ['country_code' => 'CN', 'time_zone' => 'GMT+08:00'], // China Standard Time
            ['country_code' => 'JP', 'time_zone' => 'GMT+09:00'], // Japan Standard Time
            ['country_code' => 'AU', 'time_zone' => 'GMT+10:00'], // Eastern Australia Standard Time
            ['country_code' => 'SB', 'time_zone' => 'GMT+11:00'], // Solomon Islands Time
            ['country_code' => 'NZ', 'time_zone' => 'GMT+12:00'], // New Zealand Standard Time
            ['country_code' => 'TO', 'time_zone' => 'GMT+13:00'], // Tonga Time
            ['country_code' => 'KI', 'time_zone' => 'GMT+14:00'], // Line Islands Time
        ];

        // Insert all timezones
        foreach ($timezones as $timezone) {
            // Find the country by ISO code
            if (isset($countries[$timezone['country_code']])) {
                $country = $countries[$timezone['country_code']];

                TimeZone::firstOrCreate([
                    'country_id' => $country->id,
                    'time_zone' => $timezone['time_zone']
                ]);
            }
        }

    }

}
