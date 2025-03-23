<?php

return [
    'name' => 'Tenant',
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Identification
    |--------------------------------------------------------------------------
    |
    | This section configures how tenants are identified in your application.
    |
    */
    'identification' => [
        // Default identification method (domain, subdomain, path, request_data)
        'default' => 'domain',
        
        // Custom tenant resolvers
        'resolvers' => [
            // Add custom resolvers here if needed
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Database Configuration
    |--------------------------------------------------------------------------
    |
    | This section configures tenant database settings.
    |
    */
    'database' => [
        // Whether to create a separate database for each tenant
        'separate_databases' => true,
        
        // Tables that should be migrated in the tenant database
        'tenant_tables' => [
            // List tables that should be tenant-specific
            // For example: 'users', 'orders', etc.
        ],
        
        // Tables that should remain in the central database
        'central_tables' => [
            'tenants',
            'domains',
            'companies',
            // Add other central tables here
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Tenant Creation Hooks
    |--------------------------------------------------------------------------
    |
    | This section configures actions to take when a tenant is created.
    |
    */
    'hooks' => [
        // Whether to run migrations automatically when a tenant is created
        'auto_migrate' => true,
        
        // Whether to seed the tenant database when a tenant is created
        'auto_seed' => true,
        
        // Seed class to use for tenant database seeding
        'seed_class' => 'Database\\Seeders\\TenantDatabaseSeeder',
    ],
];