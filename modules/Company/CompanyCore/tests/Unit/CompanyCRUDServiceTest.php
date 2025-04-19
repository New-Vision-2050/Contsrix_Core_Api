<?php

namespace Modules\Company\CompanyCore\Tests\Unit;

use Mockery;
use Tests\TestCase;
use Modules\Company\CompanyCore\Services\CompanyCRUDService;
use Modules\Company\CompanyCore\Repositories\CompanyRepository;
use Modules\Company\CompanyCore\DTO\CreateCompanyDTO;
use Modules\Company\CompanyCore\Models\Company;
use Ramsey\Uuid\Uuid;

class CompanyCRUDServiceTest extends TestCase
{
    protected $companyRepository;
    protected $companyService;

    public function setUp(): void
    {
        parent::setUp();

        // Mock the CompanyRepository
        $this->companyRepository = Mockery::mock(CompanyRepository::class);
        $this->companyService = new CompanyCRUDService($this->companyRepository);
    }

    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_company(): void
    {
        // Create mock DTO
        $companyDTO = new CreateCompanyDTO(
            name: 'Test Company',
            userName: 'test_user',
            // email: 'test@example.com',
            // phone: '123456789',
            countryId: 1,
            // companyTypeId: 1,
            companyFieldId: 1,
            // registrationTypeId: 1,
            generalManagerId: Uuid::uuid4(),
            // registrationNo: '123456',
            // serialNo: 'abcdef'
        );

        // Create mock company object
        $mockCompany = Mockery::mock(Company::class);
        $mockCompany->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Expect createCompany to be called once and return mockCompany
        $this->companyRepository
            ->shouldReceive('createCompany')
            ->once()
            ->with($companyDTO->toArray())
            ->andReturn($mockCompany);

        // Call the service method
        $company = $this->companyService->create($companyDTO);

        // Assertions
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals(1, $company->id);
    }

    public function test_list_companies(): void
    {
        // Mock paginated response
        $mockCompanies = [
            'data' => [['id' => 1, 'name' => 'Test Company']],
            'pagination' => [ 'page' => 1, 'perPage' => 10],
        ];

        // Expect paginated method call
        $this->companyRepository
        ->shouldReceive('paginated')
        ->once()
        ->with([], 1, 10)
        ->andReturn($mockCompanies);

        // Call service method
        $result = $this->companyService->list(1, 10);

        // Assertions
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(1, $result['data']);
    }

    public function test_get_company(): void
    {
        $uuid = Uuid::uuid4();

        // Mock company instance
        $mockCompany = Mockery::mock(Company::class);
        $mockCompany->shouldReceive('getAttribute')->with('id')->andReturn($uuid);

        // Expect getCompany method call
        $this->companyRepository
            ->shouldReceive('getCompany')
            ->once()
            ->with($uuid)
            ->andReturn($mockCompany);

        // Call service method
        $company = $this->companyService->get($uuid);

        // Assertions
        $this->assertInstanceOf(Company::class, $company);
        $this->assertEquals($uuid, $company->id);
    }
}
