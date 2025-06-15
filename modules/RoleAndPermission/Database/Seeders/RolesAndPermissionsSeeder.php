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
use Ranium\SeedOnce\Traits\SeedOnce;

class RolesAndPermissionsSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        if (!tenant()) {
            $this->call(UserPermissionsTableSeeder::class);//add permissions for user module

        }

        $superAdminRole = Role::firstOrCreate(["name" => "super-admin", "company_id" => tenant("id") ?? Company::query()->first()->id], ["name" => "super-admin", "company_id" => tenant("id") ?? Company::query()->first()->id]);

        $adminRole = Role::firstOrCreate(["name" => "admin", "company_id" => tenant("id") ?? Company::query()->first()->id], ["name" => "admin", "company_id" => tenant("id") ?? Company::query()->first()->id]);

        $superAdminRole->givePermissionTo(Permission::all());
        $adminRole->givePermissionTo(Permission::all());
        if (!tenant()) {
            $user = User::first();
            setPermissionsTeamId(tenant("id") ?? Company::query()->first()->id);
            $user->assignRole('super-admin');
        }
        //TODO not created user  yet in company user if is owner will take super admin role
// else {
//            $generalManager = User::where(['company_id'=> tenant("id"),"is_owner"=>1])->first();
//            $generalManager->assignRole('super-admin');
//
//        }

    }
}
