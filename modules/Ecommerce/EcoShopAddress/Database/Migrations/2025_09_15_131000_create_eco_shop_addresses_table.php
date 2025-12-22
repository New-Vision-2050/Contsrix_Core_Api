<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class 2025_09_15_131000_create_eco_shop_addresses_table extends Migration
{
    public function up(): void
    {
        Schema::create('eco_shop_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();

            $table->string('country_id')->nullable();
            $table->string('city_id')->nullable();
            $table->string('time_zone_id')->nullable();

            $table->string('district')->nullable(); 
            $table->string('street')->nullable();

            $table->string('building_number')->nullable();
            $table->string('postal_code')->nullable();

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_shop_addresses');
    }
}
