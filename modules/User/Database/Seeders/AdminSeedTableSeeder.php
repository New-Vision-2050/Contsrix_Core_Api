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

class AdminSeedTableSeeder extends Seeder
{
    use SeedOnce;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        //if (App::environment('production') == false) {

            $companyUser = CompanyUser::firstOrCreate(['email' =>'admin@constrix-nv.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@constrix-nv.com',

                    "currency_id"=> Currency::query()->first()->id,
                    "job_title_id"=>jobTitle::query()->first()->id,
                    "country_id"=>Country::query()->first()->id,
                    "time_zone_id"=>Country::query()->first()->id,
                    "language_id"=>Language::query()->first()->id,
                ]
            );
            $companyUser->update(["global_id" => $companyUser->id]);//set global id we can make different logic  in the future


            $user = User::firstOrCreate(['email' =>'admin@constrix-nv.com'],
                [
                    'name' => 'Admin',
                    'email' => 'admin@constrix-nv.com',
                    "phone"=>"966542138116",
                    "phone_code"=>"966",
                    'password' => "Test1234",
                    "global_company_user_id"=>$companyUser->global_id,
                    "is_owner"=>1
                ]
            );
            $user->assignRole('super-admin');
        }
    //}
}
