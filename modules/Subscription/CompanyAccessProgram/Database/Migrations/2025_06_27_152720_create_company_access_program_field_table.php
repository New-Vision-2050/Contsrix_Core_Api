<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_access_program_field', function (Blueprint $table) {
            $table->uuid('company_access_program_id');
            $table->uuid('company_field_id');

            $table->foreign('company_access_program_id')->references('id')->on('company_access_programs')->onDelete('cascade');
            $table->foreign('company_field_id')->references('id')->on('company_fields')->onDelete('cascade');

            $table->primary(['company_access_program_id', 'company_field_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_access_program_field');
    }
};
