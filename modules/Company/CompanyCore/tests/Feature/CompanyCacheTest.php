<?php

namespace Modules\Company\CompanyCore\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Company\CompanyCore\Models\CompanyAddress;
use Modules\Company\CompanyCore\Models\CompanyLegalData;
use Modules\Company\CompanyCore\Models\CompanyOfficialDocument;
use Modules\Company\CompanyCore\Services\CompanyCacheService;
use Tests\TestCase;

class CompanyCacheTest extends TestCase
{
    use RefreshDatabase;
    
    /** @var CompanyCacheService */
    private $cacheService;

    public function setUp(): void
    {
        parent::setUp();
        $this->cacheService = app(CompanyCacheService::class);
        
        // Clear cache before each test
        Cache::flush();
    }

    /** @test */
    public function it_caches_current_company_data()
    {
        // Arrange: Create test data and set up request
        $company = factory(Company::class)->create();
        $this->actingAs($company->owner);
        
        // Act: Make the first request to cache data
        $this->get('/api/v1/companies/current');
        
        // Assert: Verify data was cached
        $cacheKey = $this->cacheService->generateCompanyCacheKey($company->id, null);
        $this->assertTrue(Cache::has($cacheKey));
    }
    
    /** @test */
    public function it_clears_cache_when_company_address_updated()
    {
        // Arrange: Create test data and set up cache
        $company = factory(Company::class)->create();
        $address = factory(CompanyAddress::class)->create(['company_id' => $company->id]);
        $branchId = $address->management_hierarchy_id;
        
        // Cache some company data
        $cacheKey = $this->cacheService->generateCompanyCacheKey($company->id, $branchId);
        Cache::put($cacheKey, $company, now()->addMinutes(30));
        
        // Act: Update the address which should clear the cache
        $address->update(['city' => 'New City']);
        
        // Assert: Verify cache was cleared
        $this->assertFalse(Cache::has($cacheKey));
    }
    
    /** @test */
    public function it_clears_cache_when_company_legal_data_updated()
    {
        // Arrange: Create test data and set up cache
        $company = factory(Company::class)->create();
        $legalData = factory(CompanyLegalData::class)->create(['company_id' => $company->id]);
        $branchId = $legalData->management_hierarchy_id;
        
        // Cache some company data
        $cacheKey = $this->cacheService->generateCompanyCacheKey($company->id, $branchId);
        Cache::put($cacheKey, $company, now()->addMinutes(30));
        
        // Act: Update the legal data which should clear the cache
        $legalData->update(['registration_number' => '12345-NEW']);
        
        // Assert: Verify cache was cleared
        $this->assertFalse(Cache::has($cacheKey));
    }
    
    /** @test */
    public function it_clears_cache_when_company_documents_updated()
    {
        // Arrange: Create test data and set up cache
        $company = factory(Company::class)->create();
        $document = factory(CompanyOfficialDocument::class)->create(['company_id' => $company->id]);
        $branchId = $document->management_hierarchy_id;
        
        // Cache some company data
        $cacheKey = $this->cacheService->generateCompanyCacheKey($company->id, $branchId);
        Cache::put($cacheKey, $company, now()->addMinutes(30));
        
        // Act: Update the document which should clear the cache
        $document->update(['name' => 'Updated Document']);
        
        // Assert: Verify cache was cleared
        $this->assertFalse(Cache::has($cacheKey));
    }
}
