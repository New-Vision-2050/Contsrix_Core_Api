<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            // Add new branch_ids column as JSON array
            $table->json('branch_ids')->nullable()->after('department_id');
            
            // Add branch_locations column for custom locations per branch
            $table->json('branch_locations')->nullable()->after('branch_ids');
        });

        // Migrate existing branch_id data to branch_ids array
        DB::table('attendance_constraints')
            ->whereNotNull('branch_id')
            ->chunkById(100, function ($constraints) {
                foreach ($constraints as $constraint) {
                    DB::table('attendance_constraints')
                        ->where('id', $constraint->id)
                        ->update([
                            'branch_ids' => json_encode([$constraint->branch_id])
                        ]);
                }
            });

        Schema::table('attendance_constraints', function (Blueprint $table) {
            // Drop the foreign key constraint before dropping the column
            $table->dropForeign(['branch_id']);

            // Drop the old branch_id column
            $table->dropColumn('branch_id');
            
            // Add index for better JSON query performance
            $table->index(['company_id', DB::raw('(CAST(branch_ids AS CHAR(255) ARRAY))')], 'idx_company_branch_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            // Add back the old branch_id column
            $table->uuid('branch_id')->nullable()->after('department_id');
        });

        // Migrate data back from branch_ids to branch_id (take first element)
        DB::statement("
            UPDATE attendance_constraints 
            SET branch_id = CASE 
                WHEN branch_ids IS NOT NULL AND JSON_LENGTH(branch_ids) > 0 
                THEN JSON_UNQUOTE(JSON_EXTRACT(branch_ids, '$[0]'))
                ELSE NULL 
            END
        ");

        Schema::table('attendance_constraints', function (Blueprint $table) {
            // Drop the new columns and indexes
            $table->dropIndex('idx_company_branch_ids');
            $table->dropColumn('branch_ids');
            $table->dropColumn('branch_locations');
        });

        // Re-add the foreign key constraint
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->foreign('branch_id')->references('id')->on('management_hierarchies')->onDelete('cascade');
        });
    }
};
