<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Modules\User\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;

class CompanyListTest extends TestCase
{
    protected $user;
    protected $company;

    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate:fresh');
        Artisan::call('db:seed');

        $this->user = User::first();
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

}
