<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_30_120100_create_website_theme_setting_departments_table Migration
{
    public function up()
    {
        Schema::create('website_theme_setting_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_theme_setting_id');
            $table->timestamps();

            $table->foreign('website_theme_setting_id', 'wtsd_theme_setting_fk')
                ->references('id')
                ->on('website_theme_settings')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('website_theme_setting_departments');
    }
};
