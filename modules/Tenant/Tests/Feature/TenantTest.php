<?php

namespace Modules\Tenant\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Facades\Tenant;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating a new tenant.
     *
     * @return void
     */
    public function testCreateTenant()
    {
        // Create a tenant using the command
        $this->artisan('tenant:create', [
            'name' => 'Test Tenant',
            'email' => 'test@example.com',
            '--subdomain' => 'test',
            '--plan' => 'basic',
        ])->assertSuccessful();

        // Check if the tenant was created
        $tenant = Company::where('email', 'test@example.com')->first();
        $this->assertNotNull($tenant);
        $this->assertTrue($tenant->is_tenant);
        $this->assertEquals('test', $tenant->subdomain);
        $this->assertEquals('basic', $tenant->tenant_plan);
    }

    /**
     * Test tenant schema creation.
     *
     * @return void
     */
    public function testTenantSchemaCreation()
    {
        // Skip if not using PostgreSQL
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('This test requires PostgreSQL.');
        }

        // Create a tenant
        $tenant = Company::create([
            'name' => 'Schema Test Tenant',
            'email' => 'schema@example.com',
            'phone' => '1234567890',
            'subdomain' => 'schema',
            'is_tenant' => true,
            'tenant_created_at' => now(),
            'tenant_plan' => 'basic',
        ]);

        // Create schema for the tenant
        $tenantManager = app('tenant.manager');
        $schemaCreated = $tenantManager->createTenantSchema($tenant);

        $this->assertTrue($schemaCreated);
        
        // Check if schema exists
        $schemaName = 'tenant_' . $tenant->id;
        $schemaExists = $tenantManager->schemaExists($schemaName);
        
        $this->assertTrue($schemaExists);
    }

    /**
     * Test tenant context switching.
     *
     * @return void
     */
    public function testTenantContextSwitching()
    {
        // Skip if not using PostgreSQL
        if (config('database.default') !== 'pgsql') {
            $this->markTestSkipped('This test requires PostgreSQL.');
        }

        // Create two tenants
        $tenant1 = Company::create([
            'name' => 'Tenant 1',
            'email' => 'tenant1@example.com',
            'phone' => '1111111111',
            'subdomain' => 'tenant1',
            'is_tenant' => true,
            'tenant_created_at' => now(),
            'tenant_plan' => 'basic',
        ]);

        $tenant2 = Company::create([
            'name' => 'Tenant 2',
            'email' => 'tenant2@example.com',
            'phone' => '2222222222',
            'subdomain' => 'tenant2',
            'is_tenant' => true,
            'tenant_created_at' => now(),
            'tenant_plan' => 'premium',
        ]);

        // Create schemas for both tenants
        $tenantManager = app('tenant.manager');
        $tenantManager->createTenantSchema($tenant1);
        $tenantManager->createTenantSchema($tenant2);

        // Set tenant context to tenant1
        Tenant::setTenant($tenant1);
        
        // Create a project in tenant1's schema
        DB::table('projects')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Tenant 1 Project',
            'company_user_id' => \Illuminate\Support\Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Switch to tenant2
        Tenant::setTenant($tenant2);
        
        // Create a project in tenant2's schema
        DB::table('projects')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'name' => 'Tenant 2 Project',
            'company_user_id' => \Illuminate\Support\Str::uuid()->toString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Switch back to tenant1 and check if the project exists
        Tenant::setTenant($tenant1);
        $tenant1Projects = DB::table('projects')->get();
        $this->assertEquals(1, $tenant1Projects->count());
        $this->assertEquals('Tenant 1 Project', $tenant1Projects->first()->name);

        // Switch to tenant2 and check if the project exists
        Tenant::setTenant($tenant2);
        $tenant2Projects = DB::table('projects')->get();
        $this->assertEquals(1, $tenant2Projects->count());
        $this->assertEquals('Tenant 2 Project', $tenant2Projects->first()->name);
    }

    /**
     * Test tenant middleware.
     *
     * @return void
     */
    public function testTenantMiddleware()
    {
        // Create a tenant
        $tenant = Company::create([
            'name' => 'API Test Tenant',
            'email' => 'api@example.com',
            'phone' => '3333333333',
            'subdomain' => 'api',
            'is_tenant' => true,
            'tenant_created_at' => now(),
            'tenant_plan' => 'basic',
        ]);

        // Create a user token for authentication
        // This is a simplified example - in a real app, you would use proper authentication
        $token = 'test-token';

        // Make a request with the tenant ID in the header
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'X-Tenant-ID' => $tenant->id,
        ])->getJson('/api/tenant');

        // Check if the response contains the tenant information
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $tenant->id,
                    'name' => 'API Test Tenant',
                    'email' => 'api@example.com',
                ]
            ]);
    }
}