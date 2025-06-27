<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_access_program_type', function (Blueprint $table) {
            $table->uuid('company_access_program_id');
            $table->uuid('company_type_id');

            $table->foreign('company_access_program_id')->references('id')->on('company_access_program')->onDelete('cascade');
            $table->foreign('company_type_id')->references('id')->on('company_types')->onDelete('cascade');

            $table->primary(['company_access_program_id', 'company_type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_access_program_type');
    }
};
