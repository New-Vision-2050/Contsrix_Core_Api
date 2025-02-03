<?php

namespace Modules\Company\CompanyCore\Database\Seeders;
use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\CompanyField\Database\Seeders\CompanyFieldSeederTableSeeder;
use Modules\Company\CompanyCore\CompanyType\Database\Seeders\CompanyTypeSeederTableSeeder;
use Modules\Company\CompanyCore\CompanyRegistrationType\Database\Seeders\CompanyRegistrationTypeSeederTableSeeder;

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


    }
}
