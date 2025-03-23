<?php

namespace Modules\Tenant\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Exceptions\TenantNotFoundExeption;

class TenantManager
{
    /**
     * The current tenant.
     *
     * @var Company|null
     */
    protected ?Company $tenant = null;

    /**
     * The original schema.
     *
     * @var string
     */
    protected string $originalSchema = 'public';

    /**
     * Get the current tenant.
     *
     * @return Company|null
     */
    public function getTenant(): ?Company
    {
        return $this->tenant;
    }

    /**
     * Set the current tenant.
     *
     * @param Company $tenant
     * @return void
     */
    public function setTenant(Company $tenant): void
    {
        $this->tenant = $tenant;
        $this->switchToTenant();
    }

    /**
     * Set the tenant by ID.
     *
     * @param string $tenantId
     * @return void
     * @throws TenantNotFoundExeption
     */
    public function setTenantById(string $tenantId): void
    {
        $tenant = Company::find($tenantId);

        if (!$tenant) {
            throw new TenantNotFoundExeption("Tenant with ID {$tenantId} not found");
        }

        $this->setTenant($tenant);
    }

    /**
     * Switch to the tenant's schema.
     *
     * @return void
     */
    protected function switchToTenant(): void
    {
        if (!$this->tenant) {
            return;
        }

        // Store the current schema
        $this->originalSchema = Schema::getConnection()->getConfig('schema') ?? 'public';

        // Switch to the tenant's schema
        $schemaName = $this->getTenantSchemaName();
        $this->setSchemaConnection($schemaName);
    }

    /**
     * Switch back to the original schema.
     *
     * @return void
     */
    public function resetTenant(): void
    {
        $this->setSchemaConnection($this->originalSchema);
        $this->tenant = null;
    }

    /**
     * Set the database schema connection.
     *
     * @param string $schema
     * @return void
     */
    protected function setSchemaConnection(string $schema): void
    {
        // For PostgreSQL
        if (Config::get('database.default') === 'pgsql') {
            DB::statement("SET search_path TO {$schema}");
            Config::set('database.connections.pgsql.search_path', $schema);
        }
    }

    /**
     * Get the tenant's schema name.
     *
     * @return string
     */
    public function getTenantSchemaName(): string
    {
        return 'tenant_' . $this->tenant->id;
    }

    /**
     * Check if a tenant schema exists.
     *
     * @param string $schemaName
     * @return bool
     */
    public function schemaExists(string $schemaName): bool
    {
        if (Config::get('database.default') === 'pgsql') {
            $result = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$schemaName]);
            return count($result) > 0;
        }

        return false;
    }

    /**
     * Create a new tenant schema.
     *
     * @param Company $tenant
     * @return bool
     */
    public function createTenantSchema(Company $tenant): bool
    {
        $schemaName = 'tenant_' . $tenant->id;

        if ($this->schemaExists($schemaName)) {
            return false;
        }

        if (Config::get('database.default') === 'pgsql') {
            DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
            return true;
        }

        return false;
    }

    /**
     * Run migrations for a tenant.
     *
     * @param Company $tenant
     * @return void
     */
    public function migrateTenant(Company $tenant): void
    {
        $originalTenant = $this->tenant;
        $this->setTenant($tenant);

        // Run tenant-specific migrations
        $migrationsPath = module_path('Tenant', 'Database/Migrations/Tenant');
        DB::statement("SET search_path TO {$this->getTenantSchemaName()}");
        
        // Run the migrations
        \Artisan::call('migrate', [
            '--path' => $migrationsPath,
            '--force' => true,
        ]);

        // Reset to original tenant
        if ($originalTenant) {
            $this->setTenant($originalTenant);
        } else {
            $this->resetTenant();
        }
    }
}