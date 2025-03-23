<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the tenant module.
    |
    */

    // The database connection to use for tenants
    'connection' => env('TENANT_DB_CONNECTION', 'pgsql'),

    // The default schema to use when no tenant is set
    'default_schema' => env('TENANT_DEFAULT_SCHEMA', 'public'),

    // Tables that should be in the public schema and shared across all tenants
    'shared_tables' => [
        'migrations',
        'companies',
        'company_users',
        'company_users_companies',
        'countries',
        'company_types',
        'company_fields',
        'company_registration_types',
        'job_titles',
        'time_zones',
        'languages',
        'currencies',
        'audits',
        'cache',
        'jobs',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'sessions',
    ],

    // Middleware aliases
    'middleware' => [
        'tenant' => \Modules\Tenant\Middleware\TenantMiddleware::class,
    ],

    // Domain configuration
    'domain' => [
        // Whether to use domain-based tenant identification
        'use_domains' => env('TENANT_USE_DOMAINS', false),
        
        // The central domain where the application is hosted
        'central_domain' => env('TENANT_CENTRAL_DOMAIN', 'app.example.com'),
        
        // The domain format for tenant subdomains
        'tenant_domain_format' => env('TENANT_DOMAIN_FORMAT', '{tenant}.example.com'),
    ],
];