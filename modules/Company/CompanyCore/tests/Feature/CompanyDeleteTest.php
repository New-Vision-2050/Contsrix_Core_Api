<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;
use DB;
use Modules\Company\CompanyCore\Services\CompanyTestService;

class CompanyDeleteTest extends TestCase
{
    protected $user;
    protected $company;
    private CompanyTestService $testCompanyService;

    public function setUp(): void
    {
        parent::setUp();
        DB::beginTransaction();

        $this->user = User::first();
        $this->testCompanyService = new CompanyTestService();


        $this->company = $this->testCompanyService->create();
    }
    public function tearDown(): void
    {
        parent::tearDown();
    }
    public function test_delete_companies_no_auth(): void
    {
        $response = $this->deleteJson(route('companies.delete', $this->company->id));

        $response->assertStatus(401);
    }

    public function test_delete_companies(): void
    {
        $response = $this->actingAs($this->user)
            ->deleteJson(route('companies.delete', $this->company->id));

        $response->assertStatus(204);
    }
}
