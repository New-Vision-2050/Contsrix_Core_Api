<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('country_id')->index();
            $table->string('company_type_id')->index();
            $table->string('company_field_id')->index();
            $table->string('registration_type_id')->index();
            $table->string('general_manager_id')->index();
            $table->timestamps();
        });
    }
};
