<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_22_002600_create_banner_settings_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eco_banner_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');

            //product page
            $table->string('banner_location')->default('top'); // Banner display location (top, bottom, middle)
            $table->string('banner_display_type')->default('slider'); // Banner display type (slider, grid, list)
            $table->integer('banner_count')->default(1); // Number of banners
            $table->boolean('enable_banner')->default(true); // Show dots indicators in app
            $table->string('type_page')->nullable(); // Type of page (home, category, product, etc.)

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Indexes
            $table->index('company_id');
            $table->index('type_page');
            $table->unique(['company_id', 'type_page']); // One banner setting per company per page type
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eco_banner_settings');
    }
};

