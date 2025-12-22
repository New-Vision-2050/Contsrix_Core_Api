<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_11_23_181500_create_social_media_links_table Migration
{
    public function up(): void
    {
        Schema::create('website_social_media_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // facebook, linkedin, x, youtube, etc.
            $table->string('link');
            $table->integer('status')->default(1);
            $table->uuid('company_id');
            $table->timestamps();

            $table->index('company_id');
            $table->index('type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_media_links');
    }
};
