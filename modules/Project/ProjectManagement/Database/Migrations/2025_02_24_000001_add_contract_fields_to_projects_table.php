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
        // Step 1: Rename column if it exists
        if (Schema::hasColumn('projects', 'responsible_employee_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->renameColumn('responsible_employee_id', 'manager_id');
            });
        }
        
        // Step 2: Add new columns if they don't exist, or modify if type is wrong
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        // Check branch_id column type
        $branchIdColumn = $connection->selectOne("
            SELECT COLUMN_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'projects' AND COLUMN_NAME = 'branch_id'
        ", [$database]);
        
        Schema::table('projects', function (Blueprint $table) use ($branchIdColumn) {
            if (!$branchIdColumn) {
                // Column doesn't exist, add it
                $table->unsignedBigInteger('branch_id')->nullable()->after('manager_id');
            } elseif ($branchIdColumn->COLUMN_TYPE !== 'bigint(20) unsigned') {
                // Column exists but wrong type, modify it
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            }
            
            if (!Schema::hasColumn('projects', 'project_owner_type')) {
                $table->string('project_owner_type')->nullable()->after('branch_id');
            }
            
            if (!Schema::hasColumn('projects', 'project_owner_id')) {
                $table->uuid('project_owner_id')->nullable()->after('project_owner_type');
            }
            
            if (!Schema::hasColumn('projects', 'contract_id')) {
                $table->uuid('contract_id')->nullable()->after('project_owner_id');
            }
        });
        
        // Step 3: Add indexes if they don't exist
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        // Check existing indexes
        $indexes = $connection->select("
            SELECT DISTINCT INDEX_NAME 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'projects'
        ", [$database]);
        
        $existingIndexes = array_column($indexes, 'INDEX_NAME');
        
        Schema::table('projects', function (Blueprint $table) use ($existingIndexes) {
            if (!in_array('projects_branch_id_index', $existingIndexes)) {
                $table->index('branch_id');
            }
            if (!in_array('projects_project_owner_type_index', $existingIndexes)) {
                $table->index('project_owner_type');
            }
            if (!in_array('projects_project_owner_id_index', $existingIndexes)) {
                $table->index('project_owner_id');
            }
            if (!in_array('projects_contract_id_index', $existingIndexes)) {
                $table->index('contract_id');
            }
            if (!in_array('projects_project_owner_type_project_owner_id_index', $existingIndexes)) {
                $table->index(['project_owner_type', 'project_owner_id']);
            }
        });
        
        // Step 4: Add foreign key constraint if it doesn't exist
        $foreignKeys = $connection->select("
            SELECT CONSTRAINT_NAME, COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = ? 
            AND TABLE_NAME = 'projects' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database]);
        
        $hasBranchFk = collect($foreignKeys)->contains('COLUMN_NAME', 'branch_id');
        
        if (!$hasBranchFk) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreign('branch_id')
                    ->references('id')
                    ->on('management_hierarchies')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['branch_id']);
            
            // Drop indexes
            $table->dropIndex(['project_owner_type', 'project_owner_id']);
            $table->dropIndex(['contract_id']);
            $table->dropIndex(['project_owner_id']);
            $table->dropIndex(['project_owner_type']);
            $table->dropIndex(['branch_id']);
            
            // Drop new columns
            $table->dropColumn(['contract_id', 'project_owner_id', 'project_owner_type', 'branch_id']);
            
            // Rename manager_id back to responsible_employee_id
            $table->renameColumn('manager_id', 'responsible_employee_id');
        });
    }
};
