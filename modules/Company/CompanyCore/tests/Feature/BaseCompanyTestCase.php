<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Services\CompanyTestService;
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

        $this->companyService = app(CompanyTestService::class);
        $this->user = User::firstOrFail();
        $this->company = $this->companyService->create();
    }
}
