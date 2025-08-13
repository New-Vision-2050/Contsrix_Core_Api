<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            if (!Schema::hasColumn('roles', 'company_id')) {
                $table->uuid('company_id')->nullable()->after('guard_name');
                $table->index('company_id');
            }
        });

        // Update the unique constraint to include company_id
        Schema::table('roles', function (Blueprint $table) {
            // Drop the old unique constraint
            $table->dropUnique(['name', 'guard_name']);

            // Add new unique constraint with company_id
            $table->unique(['name', 'guard_name', 'company_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            // Drop the new unique constraint
            $table->dropUnique(['name', 'guard_name', 'company_id']);

            // Restore the old unique constraint
            $table->unique(['name', 'guard_name']);

            // Drop the company_id column
            if (Schema::hasColumn('roles', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });
    }
};
