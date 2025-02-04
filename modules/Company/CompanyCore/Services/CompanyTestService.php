<?php

declare(strict_types=1);

namespace Modules\Company\CompanyCore\Services;

use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Carbon\Carbon;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyRegistrationForm\Models\CompanyRegistrationForm;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;

class CompanyTestService
{

    public function create()
    {
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

        $company = Company::create($companyData);

        return $company;
    }

}
