<?php

namespace Modules\Company\CompanyCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Database\Seeders\CompanyFieldSeederTableSeeder;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyType\Database\Seeders\CompanyTypeSeederTableSeeder;
use Modules\Company\CompanyRegistrationType\Database\Seeders\CompanyRegistrationTypeSeederTableSeeder;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

class CompanyModulesSeederTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call(CompanyFieldSeederTableSeeder::class);
        $this->call(CompanyTypeSeederTableSeeder::class);
        $this->call(CompanyRegistrationTypeSeederTableSeeder::class);

    }
}
