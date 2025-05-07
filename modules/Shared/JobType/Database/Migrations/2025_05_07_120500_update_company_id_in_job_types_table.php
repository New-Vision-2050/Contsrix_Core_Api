<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migration.
     */
    public function up(): void
    {
        // Get the first central company
        $centralCompany = DB::table('companies')
            ->where('is_central_company', 1)
            ->first();
        
        if ($centralCompany) {
            // Update null company_id values to the central company id
            DB::table('job_types')
                ->whereNull('company_id')
                ->update(['company_id' => $centralCompany->id]);
        }
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        // No need to reverse this operation since it's a data correction
    }
};
