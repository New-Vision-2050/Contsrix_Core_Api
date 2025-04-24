<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

class CompanyListTest extends BaseCompanyTestCase
{
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
