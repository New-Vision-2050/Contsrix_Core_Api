<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\User\Models\User;
use Modules\Company\CompanyCore\Services\CompanyTestService;
use Tests\TestCase;

class CompanyShowTest extends TestCase
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

    public function test_shows_a_company_no_auth(): void
    {
        $response = $this->getJson(route('companies.show',$this->company->id));

        $response->assertStatus(401);
    }
    public function test_shows_a_company(): void
    {
        $response = $this->actingAs($this->user, 'api')
                         ->getJson(route('companies.show',$this->company->id));

        $response->assertStatus(200);

        $response->assertJson([
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]
        ]);
    }
    public function tearDown(): void
    {
        $this->company->delete();

        parent::tearDown();
    }
}
