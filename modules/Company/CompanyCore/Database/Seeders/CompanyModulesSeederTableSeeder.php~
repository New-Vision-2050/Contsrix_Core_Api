<?php

namespace Modules\Company\CompanyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Database\Seeders\CompanyFieldSeederTableSeeder;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Database\Seeders\CompanyTypeSeederTableSeeder;
use Modules\Company\CompanyRegistrationType\Database\Seeders\CompanyRegistrationTypeSeederTableSeeder;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;

class CompanyModulesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CompanyFieldSeederTableSeeder::class);
        $this->call(CompanyTypeSeederTableSeeder::class);
        $this->call(CompanyRegistrationTypeSeederTableSeeder::class);

        if (App::environment('production') == false) {

            Company::firstOrCreate(
                ['name' => 'Example Company'],
                [
                    'name' => 'Example Company',
                    'email' => 'example@company.com',
                    'phone' => '123456789',
                    'country_id' => Country::query()->inRandomOrder()->first()->id,
                    "company_type_id" => CompanyType::query()->inRandomOrder()->first()->id,
                    "registration_type_id" => CompanyRegistrationType::query()->inRandomOrder()->first()->id,
                    "company_field_id" => CompanyField::query()->inRandomOrder()->first()->id,
                    "general_manager_id" => User::query()->inRandomOrder()->first()->id
                ]
            );
        }


    }
}
