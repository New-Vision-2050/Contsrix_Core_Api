<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_15_121532_create_eco_order_shops_table Migration
{
    public function up(): void
    {
        Schema::create('eco_shops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id')->index();

            // Basic shop information
            $table->string('name');
            $table->text('description')->nullable();

            // Contact information
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            $table->string('website_url')->nullable();

            // Social media links
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('snapchat_url')->nullable();
            $table->string('whatsapp_number')->nullable();

            $table->timestamps();



        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eco_shops');
    }
};
