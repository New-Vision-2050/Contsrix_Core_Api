<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        // Drop foreign keys from company_access_program_program table
        Schema::table('company_access_program_program', function (Blueprint $table) {
            // Drop the foreign key constraints
            $table->dropForeign('company_access_program_program_company_access_program_id_foreign');
            $table->dropForeign('company_access_program_program_program_id_foreign');
        });

        // Drop foreign keys from company_access_program_sub_entity table
        Schema::table('company_access_program_sub_entity', function (Blueprint $table) {
            // Drop the foreign key constraints with custom names
            $table->dropForeign('cap_se_program_fk');
            $table->dropForeign('cap_se_entity_fk');
        });
    }

    public function down()
    {
        // Recreate foreign keys in company_access_program_program table
        Schema::table('company_access_program_program', function (Blueprint $table) {
            $table->foreign('company_access_program_id')
                ->references('id')
                ->on('company_access_programs')
                ->onDelete('cascade');

            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');
        });

        // Recreate foreign keys in company_access_program_sub_entity table
        Schema::table('company_access_program_sub_entity', function (Blueprint $table) {
            $table->foreign('company_access_program_id', 'cap_se_program_fk')
                ->references('id')
                ->on('company_access_programs')
                ->onDelete('cascade');

            $table->foreign('sub_entity_id', 'cap_se_entity_fk')
                ->references('id')
                ->on('sub_entities')
                ->onDelete('cascade');
        });
    }
};
