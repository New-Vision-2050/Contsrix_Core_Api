<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant's database.
     *
     * @return void
     */
    public function run()
    {
        // Add tenant-specific seeders here
        // For example, you might want to create default records for each tenant
        
        // Create default settings for the tenant
        // \DB::table('settings')->insert([
        //     'key' => 'company_name',
        //     'value' => tenant()->name,
        // ]);
        
        // You can also use the tenant data to customize the seeding
        // $tenantData = tenant()->data;
        // if (isset($tenantData['industry'])) {
        //     // Seed industry-specific data
        // }
    }
}