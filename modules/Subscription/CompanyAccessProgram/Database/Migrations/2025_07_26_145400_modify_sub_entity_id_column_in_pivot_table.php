<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('company_access_program_sub_entity', function (Blueprint $table) {
            // First drop the unique constraint that includes sub_entity_id
            $table->dropUnique('cap_sub_entity_unique');
            
            // Modify the sub_entity_id column from uuid to string(255)
            $table->string('sub_entity_id', 255)->change();
            
            // Recreate the unique constraint with the modified column
            $table->unique(['company_access_program_id', 'sub_entity_id'], 'cap_sub_entity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('company_access_program_sub_entity', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('cap_sub_entity_unique');
            
            // Revert the sub_entity_id column back to uuid
            $table->uuid('sub_entity_id')->change();
            
            // Recreate the unique constraint
            $table->unique(['company_access_program_id', 'sub_entity_id'], 'cap_sub_entity_unique');
        });
    }
};
