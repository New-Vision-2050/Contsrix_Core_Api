<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Company\CompanyCore\Services\CompanyTestService;
use Modules\Company\CompanyField\Models\CompanyField;
use Modules\Company\CompanyRegistrationType\Models\CompanyRegistrationType;
use Modules\Company\CompanyType\Models\CompanyType;
use Modules\User\Models\User;
use Tests\TestCase;

class BaseCompanyTestCase extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $company;
    protected $companyService;

    public function setUp(): void
    {
        parent::setUp();

        // Create necessary related data for tests
        CompanyType::create(['name' => 'Test Type']);
        CompanyField::create(['name' => 'Test Field']);
        CompanyRegistrationType::create(['name' => 'Test Registration Type']);

        $this->companyService = app(CompanyTestService::class);
        $this->user = User::factory()->create();
        $this->company = $this->companyService->create();

        // Initialize tenancy for the created company
        $this->company->domains()->create(['domain' => 'test.localhost']);
        tenancy()->initialize($this->company);
    }
}
