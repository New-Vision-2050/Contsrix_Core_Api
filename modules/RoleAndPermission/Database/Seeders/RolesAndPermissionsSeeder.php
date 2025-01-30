<?php

namespace Modules\RoleAndPermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $operations  = ["create","update","delete","list","show"];
        $modules  = ["user"];
        $arr = [];
        if (App::environment('production') == false)
        {
            $superAdminRole = Role::firstOrCreate(["name"=>"super-admin"],["name"=>"super-admin"]);
            $adminRole = Role::firstOrCreate(["name"=>"admin"],["name"=>"admin"]);
            foreach ($operations as $operation)
            {
                foreach ($modules as $module)
                {
                    Permission::firstOrCreate(["name"=>$module.".".$operation],["name"=>$module.".".$operation]);
                }

            }
            $superAdminRole->givePermissionTo(Permission::all());
            $adminRole->givePermissionTo(Permission::all());


        }
    }
}
