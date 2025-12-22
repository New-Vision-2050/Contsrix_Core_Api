<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_26_121532_create_eco_clients_table Migration
{
    public function up()
    {
        Schema::create('eco_clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('phone_code')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
    }
};
