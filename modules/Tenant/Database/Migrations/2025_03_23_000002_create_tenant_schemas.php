<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Company\CompanyCore\Models\Company;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Only run this migration if using PostgreSQL
        if (config('database.default') !== 'pgsql') {
            return;
        }

        // Get all companies that are tenants
        $companies = Company::where('is_tenant', true)->get();

        foreach ($companies as $company) {
            $schemaName = 'tenant_' . $company->id;
            
            // Check if schema already exists
            $schemaExists = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = ?", [$schemaName]);
            
            if (count($schemaExists) === 0) {
                // Create schema
                DB::statement("CREATE SCHEMA IF NOT EXISTS {$schemaName}");
                
                // Update company with schema name
                $company->database_schema = $schemaName;
                $company->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // It's generally not safe to drop schemas in a down migration
        // as it would delete all tenant data. If you need to drop schemas,
        // it should be done manually or with a specific command.
    }
};