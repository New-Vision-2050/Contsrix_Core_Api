<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_23_000001_create_website_addresses_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('city_id');
            $table->uuid('company_id');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_addresses');
    }
};
