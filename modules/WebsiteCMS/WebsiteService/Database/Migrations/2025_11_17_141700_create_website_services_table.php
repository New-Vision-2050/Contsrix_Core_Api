<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_17_141700_create_website_services_table Migration
{
    public function up(): void
    {
        Schema::create('website_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_website_cms_id');
            $table->string('reference_number')->unique();
            $table->uuid('company_id');
            $table->timestamps();

            $table->foreign('category_website_cms_id')
                ->references('id')
                ->on('category_website_cms')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_services');
    }
};
