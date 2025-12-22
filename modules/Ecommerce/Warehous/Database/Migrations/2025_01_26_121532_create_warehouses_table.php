<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_01_26_121532_create_warehouses_table Migration
{
    public function up()
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->string('name');
            $table->timestamps();
        });
    }
};
