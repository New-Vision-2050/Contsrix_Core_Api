<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Modules\Company\CompanyCore\Models\Company;

class CompanyDeleteTest extends TestCase
{
    protected $user;
    protected $company;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');

        $this->user = User::first();

        $this->company = Company::first();
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

    public function tearDown(): void
    {
        parent::tearDown();
    }
}
