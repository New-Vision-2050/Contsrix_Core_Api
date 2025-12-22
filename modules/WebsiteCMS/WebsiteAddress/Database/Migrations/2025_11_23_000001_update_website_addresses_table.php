<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_23_000001_update_website_addresses_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('website_addresses', function (Blueprint $table) {
            $table->dropColumn(['city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('website_addresses', function (Blueprint $table) {
            $table->unsignedBigInteger("city_id");
        });    }
};
