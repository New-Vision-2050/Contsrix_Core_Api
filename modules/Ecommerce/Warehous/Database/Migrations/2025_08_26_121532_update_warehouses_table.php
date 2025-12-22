<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_26_121532_update_warehouses_table Migration
{
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->boolean('is_default')->default(0);
            $table->uuid('country_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('district')->nullable();
            $table->string('street')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
        });
    }
};
