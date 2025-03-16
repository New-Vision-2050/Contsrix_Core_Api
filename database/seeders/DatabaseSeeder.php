<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Database\Seeders\CompanyModulesSeederTableSeeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;
use Modules\Country\Database\Seeders\CountrySeederTableSeeder;
use Modules\JobTitle\Database\Seeders\JobTitleModulesSeederTableSeeder;

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
        
        $this->call(JobTitleModulesSeederTableSeeder::class);
    }
}
