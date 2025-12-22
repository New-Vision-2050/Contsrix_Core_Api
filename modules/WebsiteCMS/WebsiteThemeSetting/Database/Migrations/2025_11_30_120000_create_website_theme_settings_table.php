<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_30_120000_create_website_theme_settings_table Migration
{
    public function up()
    {
        Schema::create('website_theme_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->boolean('is_default')->default(false);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_theme_settings');
    }
};
