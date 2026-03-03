<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop foreign keys first (they depend on indexes)
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_request_service_term' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        foreach ($foreignKeys as $fk) {
            DB::statement("ALTER TABLE client_request_service_term DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
        }
        
        // Drop the composite primary key if it exists
        $indexExists = DB::select("SHOW KEYS FROM client_request_service_term WHERE Key_name = 'PRIMARY'");
        if (!empty($indexExists)) {
            $columns = array_column($indexExists, 'Column_name');
            if (in_array('client_request_id', $columns) && in_array('client_request_service_id', $columns)) {
                Schema::table('client_request_service_term', function (Blueprint $table) {
                    $table->dropPrimary(['client_request_id', 'client_request_service_id']);
                });
            }
        }
        
        // Drop old indexes
        $oldIndexes = ['crs_service_term_request_idx', 'crs_service_term_service_idx'];
        foreach ($oldIndexes as $oldIndex) {
            $indexExists = DB::select("SHOW KEYS FROM client_request_service_term WHERE Key_name = ?", [$oldIndex]);
            if (!empty($indexExists)) {
                Schema::table('client_request_service_term', function (Blueprint $table) use ($oldIndex) {
                    $table->dropIndex($oldIndex);
                });
            }
        }
        
        // Add id column as primary key
        if (!Schema::hasColumn('client_request_service_term', 'id')) {
            Schema::table('client_request_service_term', function (Blueprint $table) {
                $table->id()->first();
            });
        }
        
        // Add company_id column
        if (!Schema::hasColumn('client_request_service_term', 'company_id')) {
            Schema::table('client_request_service_term', function (Blueprint $table) {
                $table->uuid('company_id')->after('term_ids');
            });
        }
        
        // Add timestamps
        if (!Schema::hasColumn('client_request_service_term', 'created_at')) {
            Schema::table('client_request_service_term', function (Blueprint $table) {
                $table->timestamps();
            });
        }
        
        // Add new indexes and foreign keys
        $existingIndexes = DB::select("SHOW KEYS FROM client_request_service_term");
        $indexNames = array_column($existingIndexes, 'Key_name');
        
        Schema::table('client_request_service_term', function (Blueprint $table) use ($indexNames) {
            if (!in_array('crst_request_idx', $indexNames)) {
                $table->index('client_request_id', 'crst_request_idx');
            }
            if (!in_array('crst_service_idx', $indexNames)) {
                $table->index('client_request_service_id', 'crst_service_idx');
            }
            if (!in_array('crst_company_idx', $indexNames)) {
                $table->index('company_id', 'crst_company_idx');
            }
        });
        
        // Add foreign keys
        $existingForeignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_request_service_term' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $fkNames = array_column($existingForeignKeys, 'CONSTRAINT_NAME');
        
        Schema::table('client_request_service_term', function (Blueprint $table) use ($fkNames) {
            // Check if foreign keys don't already exist before adding
            $hasRequestFk = false;
            $hasServiceFk = false;
            $hasCompanyFk = false;
            
            foreach ($fkNames as $fkName) {
                if (strpos($fkName, 'client_request_id') !== false) $hasRequestFk = true;
                if (strpos($fkName, 'client_request_service_id') !== false) $hasServiceFk = true;
                if (strpos($fkName, 'company_id') !== false) $hasCompanyFk = true;
            }
            
            if (!$hasRequestFk) {
                $table->foreign('client_request_id')
                      ->references('id')
                      ->on('client_requests')
                      ->onDelete('cascade');
            }
            
            if (!$hasServiceFk) {
                $table->foreign('client_request_service_id')
                      ->references('id')
                      ->on('client_request_services')
                      ->onDelete('cascade');
            }
            
            if (!$hasCompanyFk) {
                $table->foreign('company_id')
                      ->references('id')
                      ->on('companies')
                      ->onDelete('cascade');
            }
        });
    }

    public function down()
    {
        Schema::table('client_request_service_term', function (Blueprint $table) {
            // Drop foreign keys
            $table->dropForeign(['client_request_id']);
            $table->dropForeign(['client_request_service_id']);
            
            if (Schema::hasColumn('client_request_service_term', 'company_id')) {
                $table->dropForeign(['company_id']);
                $table->dropIndex('crst_company_idx');
                $table->dropColumn('company_id');
            }
            
            // Drop indexes
            $table->dropIndex('crst_request_idx');
            $table->dropIndex('crst_service_idx');
            
            if (Schema::hasColumn('client_request_service_term', 'created_at')) {
                $table->dropColumn(['created_at', 'updated_at']);
            }
            
            if (Schema::hasColumn('client_request_service_term', 'id')) {
                $table->dropColumn('id');
            }
            
            // Restore composite primary key and old structure
            $table->primary(['client_request_id', 'client_request_service_id']);
            
            $table->foreign('client_request_id')
                  ->references('id')
                  ->on('client_requests')
                  ->onDelete('cascade');
                  
            $table->foreign('client_request_service_id')
                  ->references('id')
                  ->on('client_request_services')
                  ->onDelete('cascade');
        });
    }
};
