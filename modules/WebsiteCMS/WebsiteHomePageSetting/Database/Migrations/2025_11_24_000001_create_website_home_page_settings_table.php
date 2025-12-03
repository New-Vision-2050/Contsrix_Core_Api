<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('website_home_page_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('web_video_link')->nullable();
            $table->string('mobile_video_link')->nullable();
            $table->boolean('is_companies')->default(false);
            $table->boolean('is_approvals')->default(false);
            $table->boolean('is_certificates')->default(false);
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_home_page_settings');
    }
};
