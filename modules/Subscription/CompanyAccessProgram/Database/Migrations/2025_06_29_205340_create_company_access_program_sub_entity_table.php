<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_access_program_sub_entity', function (Blueprint $table) {
            $table->uuid('company_access_program_id');
            $table->uuid('sub_entity_id');

            $table->foreign('company_access_program_id', 'cap_se_program_fk')
                ->references('id')
                ->on('company_access_programs')
                ->onDelete('cascade');

            $table->foreign('sub_entity_id', 'cap_se_entity_fk')
                ->references('id')
                ->on('sub_entities')
                ->onDelete('cascade');

            $table->unique(['company_access_program_id', 'sub_entity_id'], 'cap_sub_entity_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_access_program_sub_entity');
    }
};
