<?php

namespace Modules\RoleAndPermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Database\Seeders\UserPermissionsTableSeeder;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        $this->call(UserPermissionsTableSeeder::class);//add permissions for user module

        if (App::environment('production') == false)
        {
            $superAdminRole = Role::firstOrCreate(["name"=>"super-admin"],["name"=>"super-admin"]);
            $adminRole = Role::firstOrCreate(["name"=>"admin"],["name"=>"admin"]);

            $superAdminRole->givePermissionTo(Permission::all());
            $adminRole->givePermissionTo(Permission::all());


        }
    }
}
