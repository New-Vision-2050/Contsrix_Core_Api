<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_30_120200_create_company_website_theme_settings_table Migration
{
    public function up()
    {
        Schema::create('company_website_theme_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('website_theme_setting_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            $table->foreign('company_id', 'cwts_company_fk')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');

            $table->foreign('website_theme_setting_id', 'cwts_theme_setting_fk')
                ->references('id')
                ->on('website_theme_settings')
                ->onDelete('cascade');

            // Ensure one theme setting per company
            $table->unique('company_id', 'cwts_company_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_website_theme_settings');
    }
};
