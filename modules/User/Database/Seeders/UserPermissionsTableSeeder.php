<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;

class UserPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $operations  = ["create","update","delete","list","show"];
        $modules  = ["user"];
        if (App::environment('production') == false)
        {

            foreach ($operations as $operation)
            {
                foreach ($modules as $module)
                {
                    Permission::firstOrCreate(["name"=>$module.".".$operation],["name"=>$module.".".$operation]);
                }

            }

        }

        // $this->call("OthersTableSeeder");
    }
}
