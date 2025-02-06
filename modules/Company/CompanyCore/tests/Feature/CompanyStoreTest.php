<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\Country\Models\Country;
use Modules\User\Models\User;
use Tests\TestCase;

class CompanyStoreTest extends TestCase
{
    protected $user;
    protected $companyData;
    public function setUp(): void
    {

        parent::setUp();
        $this->user = User::first();

        $country = Country::first();
        $companyType = CompanyType::first();
        $companyField = CompanyField::first();
        $registrationType = CompanyRegistrationType::first();
        $general_manager = User::first();

        $this->companyData = [
            'name' => 'تجربة شركة',
            'user_name' => bin2hex(random_bytes(6)),
            'email' => 'test@example.com',
            'phone' => '123456789',
            'country_id' => $country->id,
            'company_type_id' => $companyType->id,
            'company_field_id' => $companyField->id,
            'registration_type_id' => $registrationType->id,
            'general_manager_id' => $general_manager->id->toString(),
            'registration_type' => 1,
            'registration_no' => '123456',
            'serial_no' => bin2hex(random_bytes(6))
        ];
    }
    public function tearDown(): void
    {
        parent::tearDown();
    }
    public function test_stores_companies_no_auth(): void
    {
        $response = $this->postJson(route('companies.store'),$this->companyData);

        $response->assertStatus(401);
    }
    public function test_stores_companies_no_data(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('companies.store'));

        $response->assertStatus(422);
    }
    public function test_stores_companies(): void
    {
        $response = $this->actingAs($this->user)
                ->postJson(route('companies.store'),$this->companyData);

        $response->assertStatus(200);
    }

}
