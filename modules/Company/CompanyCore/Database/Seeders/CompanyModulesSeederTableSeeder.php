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
use Modules\CompanyUser\Enum\CompanyUserRole;
use Modules\CompanyUser\Models\CompanyUserCompany;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;

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

        $country = Country::first();
        $companyType = CompanyType::first();
        $companyField = CompanyField::first();
        $registrationType = CompanyRegistrationType::first();
        $general_manager = User::first();

        $companyData = [
            'name' => 'Test Company',
            'user_name' => bin2hex(random_bytes(6)),
            'email' => 'test@example.com',
            'phone' => '123456789',
            'country_id' => $country->id,
            'company_type_id' => $companyType->id,
            'company_field_id' => $companyField->id,
            'registration_type_id' => $registrationType->id,
            'general_manager_id' => $general_manager->id->toString(),
            'registration_no' => '123456',
            'serial_no'=> bin2hex(random_bytes(6))
        ];

        $company = Company::firstOrCreate(
            ['email' => $companyData['email']],
            $companyData
        );

        $general_manager->update(['company_id' => $company->id]);
        CompanyUserCompany::query()->create([
            'company_id' => $company->id,
            'global_company_user_id' => $general_manager->global_company_user_id,
            'role' => CompanyUserRole::EMPLOYEE->value
        ]);

    }
}
