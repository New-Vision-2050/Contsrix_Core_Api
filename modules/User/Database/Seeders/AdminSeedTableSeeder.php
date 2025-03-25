<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

class AdminSeedTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        if (App::environment('production') == false) {

            $user = User::firstOrCreate(['email' =>'admin@constrix-nv.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@constrix-nv.com',
                    "phone"=>"542138116",
                    "phone_code"=>"966",
                    'password' => "Test1234",
                    "global_company_user_id"=>Uuid::uuid4()->toString()
                ]
            );
            $user->assignRole('super-admin');
        }
    }
}
