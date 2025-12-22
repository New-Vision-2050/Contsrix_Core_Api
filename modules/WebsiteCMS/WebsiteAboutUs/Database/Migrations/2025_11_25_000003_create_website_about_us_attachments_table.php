<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_25_000003_create_website_about_us_attachments_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_about_us_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('website_about_us_id');
            $table->string('name');
            $table->timestamps();

            $table->foreign('website_about_us_id')->references('id')->on('website_about_us')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_about_us_attachments');
    }
};
