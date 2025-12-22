<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class 2025_08_27_121532_create_eco_addresses_table Migration
{
    public function up()
    {
        Schema::create('eco_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();
            $table->uuid('eco_client_id')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone_code');
            $table->string('phone');
            $table->uuid('country_id');
            $table->uuid('city_id');
            $table->uuid('state_id');
            $table->string('address');

            $table->string('zip_code')->nullable(); // Zip/postal code
            $table->string('type')->default('shipping'); // e.g., 'shipping', 'billing'
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eco_addresses');
    }
};
