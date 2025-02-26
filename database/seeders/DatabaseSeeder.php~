<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Database\Seeders\CompanyModulesSeederTableSeeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Setting\Database\Seeders\DefaultIdentifierSeederTableSeeder;
use Modules\Setting\Database\Seeders\DefaultLoginWaySeederTableSeeder;
use Modules\Setting\Database\Seeders\DriverTableSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);

        $this->call(AdminSeedTableSeeder::class);
        $this->call(SettingSeeder::class);
        $this->call(CountrySeederTableSeeder::class);
        $this->call(CompanyModulesSeederTableSeeder::class);
        $this->call(DefaultLoginWaySeederTableSeeder::class);
        $this->call(DefaultIdentifierSeederTableSeeder::class);
        $this->call(DriverTableSeeder::class);
    }
}
