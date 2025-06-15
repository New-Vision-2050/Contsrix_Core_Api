<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\Setting\Database\Seeders\QuestionSettingTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
//        $this->call(CountrySeederTableSeeder::class);
//        $this->call(TimeZoneSeederTableSeeder::class);
//        $this->call(LanguageSeederTableSeeder::class);
//        $this->call(CurrencySeederTableSeeder::class);
//        $this->call(AdminSeedTableSeeder::class);

//        $this->call(CompanyModulesSeederTableSeeder::class);

        $this->call(RolesAndPermissionsSeeder::class);

        $this->call(JobTitleModulesSeederTableSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(DriverTableSeeder::class);
        $this->call(QuestionSettingTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);

        $this->call(DefaultLoginWaySeederTableSeeder::class);
    }
}
