<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_access_program_module', function (Blueprint $table) {
            $table->uuid('company_access_program_id');
            $table->uuid('module_id');

            $table->foreign('company_access_program_id')
                ->references('id')
                ->on('company_access_programs')
                ->onDelete('cascade');

            $table->foreign('module_id')
                ->references('id')
                ->on('modules')
                ->onDelete('cascade');

            $table->primary(['company_access_program_id', 'module_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_access_program_module');
    }
};
