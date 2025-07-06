<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_access_program_program', function (Blueprint $table) {
            $table->uuid('company_access_program_id');
            $table->uuid('program_id');

            $table->foreign('company_access_program_id')
                ->references('id')
                ->on('company_access_programs')
                ->onDelete('cascade');

            $table->foreign('program_id')
                ->references('id')
                ->on('programs')
                ->onDelete('cascade');

            $table->unique(['company_access_program_id', 'program_id'], 'cap_program_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_access_program_program');
    }
};
