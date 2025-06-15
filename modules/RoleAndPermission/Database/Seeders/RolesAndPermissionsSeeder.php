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
    use SeedOnce;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (!tenant()) {
            $this->call(UserPermissionsTableSeeder::class);//add permissions for user module

        }


        $superAdminRole = Role::firstOrCreate(["name" => "super-admin"], ["name" => "super-admin", "company_id" => tenant("id") ?? Company::query()->first()->id]);
        $adminRole = Role::firstOrCreate(["name" => "admin"], ["name" => "admin", "company_id" => tenant("id") ?? Company::query()->first()->id]);
        $superAdminRole->givePermissionTo(Permission::all());
        $adminRole->givePermissionTo(Permission::all());
        if (!tenant()) {
            $user = User::first();
            setPermissionsTeamId(tenant("id") ?? Company::query()->first()->id);
            $user->assignRole('super-admin');
        } else {
            $generalManagerId = tenant("general_manager_id");
            $generalManager = User::where('id', $generalManagerId)->first();
            $generalManager->assignRole('super-admin');

        }

    }
}
