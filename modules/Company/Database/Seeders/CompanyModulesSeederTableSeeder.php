<?php

namespace Modules\Company\Database\Seeders;
use Illuminate\Database\Seeder;
use Modules\Company\CompanyField\Database\Seeders\CompanyFieldSeederTableSeeder;
use Modules\Company\CompanyType\Database\Seeders\CompanyTypeSeederTableSeeder;
use Modules\Company\RegistrationType\Database\Seeders\RegistrationTypeSeederTableSeeder;

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
        $this->call(RegistrationTypeSeederTableSeeder::class);


    }
}
