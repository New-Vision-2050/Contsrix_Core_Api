<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\CompanyUser\Models\CompanyUser;
use Modules\Country\Models\Country;
use Modules\JobTitle\Models\JobTitle;
use Modules\Shared\Currency\Models\Currency;
use Modules\Shared\Language\Models\Language;
use Modules\Shared\TimeZone\Models\TimeZone;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;
use Ranium\SeedOnce\Traits\SeedOnce;

class GenaralAdminSeedTableSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //if (App::environment('production') == false) {

        $user = User::firstOrCreate(['email' => 'admin@constrix-nv.com',],
            [
                'name' => 'Admin',
                'email' => 'admin@constrix-nv.com',
                "phone" => "966542138116",
                "phone_code" => "966",
                'password' => "Test1234",
                "global_company_user_id" => CompanyUser::query()->withoutParentModel()->where("email", "admin@constrix-nv.com")->first()->global_id,
                "company_id" => tenant("id"),
            ]
        );
    }
    //}
}
