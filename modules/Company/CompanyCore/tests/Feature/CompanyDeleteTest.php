<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

class CompanyDeleteTest extends BaseCompanyTestCase
{
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
