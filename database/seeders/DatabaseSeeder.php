<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Database\Seeders\CompanyModulesSeederTableSeeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\Shared\Currency\Database\Seeders\CurrencySeederTableSeeder;
use Modules\Shared\Language\Database\Seeders\LanguageSeederTableSeeder;
use Modules\Shared\TimeZone\Database\Seeders\TimeZoneSeederTableSeeder;
use Ranium\SeedOnce\Traits\SeedOnce;

class DatabaseSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(CountrySeederTableSeeder::class);
        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(TimeZoneSeederTableSeeder::class);
        $this->call(LanguageSeederTableSeeder::class);
        $this->call(CurrencySeederTableSeeder::class);
        $this->call(AdminSeedTableSeeder::class);

        $this->call(CompanyModulesSeederTableSeeder::class);

        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);


    }
}
