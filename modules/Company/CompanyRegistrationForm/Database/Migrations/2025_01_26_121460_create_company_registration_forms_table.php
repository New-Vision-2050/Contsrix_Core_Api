<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_registration_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_id')->index();
            $table->string('registration_no')->nullable();
            $table->timestamps();
        });
    }
};
