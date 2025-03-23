<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Tenant\Models\Tenant;
use Stancl\Tenancy\Database\Models\Domain;

class TestTenantSchemaCreation extends Command
{
    protected $signature = 'test:tenant-schema';
    protected $description = 'Test tenant schema creation in PostgreSQL';

    public function handle()
    {
        $this->info('Testing PostgreSQL schema creation directly...');

        // Generate a test tenant ID
        $tenantId = (string) Str::uuid();
        $this->info('Using test tenant ID: ' . $tenantId);

        // Create a tenant record directly
        $tenant = new Tenant([
            'id' => $tenantId,
            'name' => 'Test Tenant ' . now()->format('Y-m-d H:i:s'),
        ]);
        $tenant->save();
        $this->info('Created test tenant record');

        // Create a domain for the tenant
        $domain = new Domain([
            'domain' => 'test-' . Str::random(6) . '.' . config('tenancy.central_domains.0', 'localhost'),
            'tenant_id' => $tenantId
        ]);
        $domain->save();
        $this->info('Created domain: ' . $domain->domain);

        // Try to create the tenant schema directly
        try {
            $schemaName = 'tenant_' . $tenant->id;
            $this->info('Attempting to create schema: ' . $schemaName);
            
            // Check if schema exists
            $exists = $tenant->database()->manager()->databaseExists($schemaName);
            $this->info('Schema already exists: ' . ($exists ? 'Yes' : 'No'));
            
            if (!$exists) {
                // Create the schema
                $tenant->database()->manager()->createDatabase($schemaName);
                $this->info('Created new schema successfully');
                
                // Verify schema now exists
                $nowExists = $tenant->database()->manager()->databaseExists($schemaName);
                $this->info('Schema now exists: ' . ($nowExists ? 'Yes' : 'No'));
                
                // Try to run migrations
                $this->info('Running migrations for the new tenant...');
                \Artisan::call('tenants:migrate', [
                    '--tenants' => [$tenant->id]
                ]);
                $this->info('Migrations completed');
            }
            
            $this->info('Test completed successfully!');
            
        } catch (\Exception $e) {
            $this->error('Error creating schema: ' . $e->getMessage());
            throw $e;
        }
    }
}