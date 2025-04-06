<?php

namespace Modules\RoleAndPermission\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\RoleAndPermission\Models\Permission;
use Modules\RoleAndPermission\Models\Role;
use Modules\User\Database\Seeders\UserPermissionsTableSeeder;
use Modules\User\Models\User;

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

        if (App::environment('production') == false) {
            $superAdminRole = Role::firstOrCreate(["name" => "super-admin"], ["name" => "super-admin","company_id"=>tenant("id")??Company::query()->first()->id]);
            $adminRole = Role::firstOrCreate(["name" => "admin"], ["name" => "admin","company_id"=>tenant("id")??Company::query()->first()->id]);
        }
        $superAdminRole->givePermissionTo(Permission::all());
        $adminRole->givePermissionTo(Permission::all());
        $user =  User::first();
        setPermissionsTeamId(tenant("id")??Company::query()->first()->id);


        $user->assignRole('super-admin');

    }
}
