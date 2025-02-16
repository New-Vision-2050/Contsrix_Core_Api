<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

class CompanyShowTest extends BaseCompanyTestCase
{
    public function test_shows_a_company_no_auth(): void
    {
        $response = $this->getJson(route('companies.show', $this->company->id));
        $response->assertStatus(401);
    }

    public function test_shows_a_company(): void
    {
        $response = $this->actingAs($this->user, 'api')
                         ->getJson(route('companies.show', $this->company->id));

        $response->assertStatus(200);
        $response->assertJson([
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
            ]
        ]);
    }
}
