<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_10_22_121645_create_units_table Migration
{
    public function up()
    {
        Schema::create('units', function (Blueprint $table){
            $table->uuid('id')->primary();
            $table->string("name");
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('units');
    }
};
