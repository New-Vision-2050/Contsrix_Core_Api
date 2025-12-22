<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_25_000002_create_website_about_us_project_types_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_about_us_project_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_about_us_id');
            $table->integer('count')->default(0);
            $table->timestamps();

            $table->foreign('website_about_us_id')->references('id')->on('website_about_us')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_about_us_project_types');
    }
};
