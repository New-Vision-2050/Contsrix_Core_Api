<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_19_161700_create_website_icons_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_icons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_website_cms_id');
            $table->uuid('company_id');
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('category_website_cms_id')
                ->references('id')
                ->on('category_website_cms')
                ->onDelete('cascade');

            $table->foreign('company_id')
                ->references('id')
                ->on('companies')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_icons');
    }
};
