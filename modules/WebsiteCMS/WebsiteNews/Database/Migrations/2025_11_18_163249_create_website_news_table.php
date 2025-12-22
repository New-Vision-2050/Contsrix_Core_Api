<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_18_163249_create_website_news_table Migration
{
    public function up(): void
    {
        Schema::create('website_news', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_website_cms_id');
            $table->uuid('company_id');
            $table->date('publish_date');
            $table->date('end_date')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('category_website_cms_id')
                ->references('id')
                ->on('category_website_cms')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_news');
    }
};
