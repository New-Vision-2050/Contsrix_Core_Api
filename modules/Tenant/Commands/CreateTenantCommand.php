<?php

namespace Modules\Tenant\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Tenant\Services\TenantManager;

class CreateTenantCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:create 
                            {name : The name of the tenant} 
                            {email : The email of the tenant} 
                            {--subdomain= : The subdomain for the tenant} 
                            {--plan= : The plan for the tenant} 
                            {--country_id= : The country ID for the tenant} 
                            {--company_type_id= : The company type ID for the tenant} 
                            {--company_field_id= : The company field ID for the tenant} 
                            {--registration_type_id= : The registration type ID for the tenant}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new tenant with its own schema';

    /**
     * @var TenantManager
     */
    protected $tenantManager;

    /**
     * Create a new command instance.
     *
     * @param TenantManager $tenantManager
     */
    public function __construct(TenantManager $tenantManager)
    {
        parent::__construct();
        $this->tenantManager = $tenantManager;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Creating new tenant...');

        try {
            DB::beginTransaction();

            // Create the tenant (company)
            $tenant = $this->createTenant();

            // Create the schema for the tenant
            $this->createTenantSchema($tenant);

            // Run migrations for the tenant
            $this->migrateTenant($tenant);

            DB::commit();

            $this->info('Tenant created successfully!');
            $this->info("Tenant ID: {$tenant->id}");
            $this->info("Tenant Name: {$tenant->name}");
            $this->info("Tenant Subdomain: {$tenant->subdomain}");
            $this->info("Tenant Schema: {$tenant->database_schema}");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Failed to create tenant: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * Create a new tenant.
     *
     * @return Company
     */
    protected function createTenant(): Company
    {
        $name = $this->argument('name');
        $email = $this->argument('email');
        $subdomain = $this->option('subdomain') ?? Str::slug($name);
        $plan = $this->option('plan') ?? 'basic';

        // Check if subdomain is already taken
        $existingTenant = Company::where('subdomain', $subdomain)->first();
        if ($existingTenant) {
            throw new \Exception("Subdomain '{$subdomain}' is already taken.");
        }

        // Create the tenant
        $tenant = new Company();
        $tenant->name = $name;
        $tenant->email = $email;
        $tenant->subdomain = $subdomain;
        $tenant->is_tenant = true;
        $tenant->tenant_created_at = now();
        $tenant->tenant_plan = $plan;
        
        // Set optional fields if provided
        if ($this->option('country_id')) {
            $tenant->country_id = $this->option('country_id');
        }
        
        if ($this->option('company_type_id')) {
            $tenant->company_type_id = $this->option('company_type_id');
        }
        
        if ($this->option('company_field_id')) {
            $tenant->company_field_id = $this->option('company_field_id');
        }
        
        if ($this->option('registration_type_id')) {
            $tenant->registration_type_id = $this->option('registration_type_id');
        }

        // Generate a phone number if not provided
        $tenant->phone = $this->option('phone') ?? '+1' . rand(1000000000, 9999999999);

        $tenant->save();

        return $tenant;
    }

    /**
     * Create a schema for the tenant.
     *
     * @param Company $tenant
     * @return void
     */
    protected function createTenantSchema(Company $tenant): void
    {
        $schemaName = 'tenant_' . $tenant->id;
        
        $this->info("Creating schema '{$schemaName}'...");
        
        if ($this->tenantManager->schemaExists($schemaName)) {
            $this->warn("Schema '{$schemaName}' already exists.");
            return;
        }
        
        $created = $this->tenantManager->createTenantSchema($tenant);
        
        if ($created) {
            $tenant->database_schema = $schemaName;
            $tenant->save();
            $this->info("Schema '{$schemaName}' created successfully.");
        } else {
            throw new \Exception("Failed to create schema '{$schemaName}'.");
        }
    }

    /**
     * Run migrations for the tenant.
     *
     * @param Company $tenant
     * @return void
     */
    protected function migrateTenant(Company $tenant): void
    {
        $this->info("Running migrations for tenant '{$tenant->name}'...");
        $this->tenantManager->migrateTenant($tenant);
        $this->info("Migrations completed successfully.");
    }
}