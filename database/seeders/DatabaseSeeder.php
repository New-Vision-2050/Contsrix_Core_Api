<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\RoleAndPermission\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\User\Database\Seeders\AdminSeedTableSeeder;

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
    }
}
