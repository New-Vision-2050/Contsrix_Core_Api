<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use DB;
use Modules\Company\CompanyCore\Services\CompanyTestService;

use Modules\User\Models\User;
use Tests\TestCase;

class CompanyListTest extends TestCase
{
    protected $user;
    protected $company;
    private CompanyTestService $testCompanyService;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = User::first();
        $this->testCompanyService = new CompanyTestService();

        $this->company = $this->testCompanyService->create();
    }

    public function test_lists_companies_no_auth(): void
    {
        $response = $this->getJson(route('companies.index'));

        $response->assertStatus(401);
    }

    public function test_lists_companies(): void
    {
        $response = $this->actingAs($this->user, 'api')
                         ->getJson(route('companies.index'));

        $response->assertStatus(200);

    }
    
    public function tearDown(): void
    {
        // Assuming the company has an ID and can be deleted like this:
        $this->company->delete();

        parent::tearDown();
    }
}
