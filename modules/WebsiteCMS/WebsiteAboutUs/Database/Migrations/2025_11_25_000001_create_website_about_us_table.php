<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_25_000001_create_website_about_us_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_about_us', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('title');
            $table->longText('description');
            $table->boolean('is_certificates')->default(false);
            $table->boolean('is_approvals')->default(false);
            $table->boolean('is_companies')->default(false);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_about_us');
    }
};
