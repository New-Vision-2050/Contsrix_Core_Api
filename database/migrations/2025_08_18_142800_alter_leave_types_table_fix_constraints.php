<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Change name from JSON to string to fix constraint violation
            $table->string('name', 255)->change();
            
            // Add unique constraint for name per company
            $table->unique(['company_id', 'name'], 'leave_types_company_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('leave_types', function (Blueprint $table) {
            // Drop unique constraint
            $table->dropUnique('leave_types_company_name_unique');
            
            // Change name back to JSON
            $table->json('name')->change();
        });
    }
};
