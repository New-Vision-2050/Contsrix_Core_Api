<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\Company\CompanyCore\Services\CompanyTestService;

class CompanyStoreTest extends BaseCompanyTestCase
{
    protected $companyData;
    protected CompanyTestService $companyTestService;

    public function setUp(): void
    {
        parent::setUp();

        $this->companyTestService = app(CompanyTestService::class);

        $this->companyData = $this->companyTestService->generateTestData();

        $this->companyData['general_manager_id'] = $this->user->id->toString();
    }

    public function test_stores_companies_no_auth(): void
    {
        $response = $this->postJson(route('companies.store'), $this->companyData);
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
                ->postJson(route('companies.store'), $this->companyData);
        $response->assertStatus(200);
    }
}
