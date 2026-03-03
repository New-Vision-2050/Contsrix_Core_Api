<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Drop the existing foreign key
        $foreignKeys = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'client_request_service_term' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        foreach ($foreignKeys as $fk) {
            if (strpos($fk->CONSTRAINT_NAME, 'client_request_service_id') !== false) {
                DB::statement("ALTER TABLE client_request_service_term DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
            }
        }
        
        // Drop the index
        $indexes = DB::select("SHOW KEYS FROM client_request_service_term WHERE Column_name = 'client_request_service_id'");
        foreach ($indexes as $index) {
            if ($index->Key_name !== 'PRIMARY') {
                DB::statement("ALTER TABLE client_request_service_term DROP INDEX {$index->Key_name}");
            }
        }
        
        // Rename the column
        Schema::table('client_request_service_term', function (Blueprint $table) {
            $table->renameColumn('client_request_service_id', 'term_service_setting_id');
        });
        
        // Add new index and foreign key
        Schema::table('client_request_service_term', function (Blueprint $table) {
            $table->index('term_service_setting_id', 'crst_term_service_setting_idx');
            
            $table->foreign('term_service_setting_id')
                  ->references('id')
                  ->on('term_service_settings')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        // Drop foreign key and index
        Schema::table('client_request_service_term', function (Blueprint $table) {
            $table->dropForeign(['term_service_setting_id']);
            $table->dropIndex('crst_term_service_setting_idx');
        });
        
        // Rename back
        Schema::table('client_request_service_term', function (Blueprint $table) {
            $table->renameColumn('term_service_setting_id', 'client_request_service_id');
        });
        
        // Restore old foreign key
        Schema::table('client_request_service_term', function (Blueprint $table) {
            $table->index('client_request_service_id', 'crst_service_idx');
            
            $table->foreign('client_request_service_id')
                  ->references('id')
                  ->on('client_request_services')
                  ->onDelete('cascade');
        });
    }
};
