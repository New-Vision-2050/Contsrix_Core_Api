<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_09_21_132500_create_eco_filter_settings_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('eco_filter_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            // Filter Settings 
            $table->string('filter_name'); // Filter name
            $table->string('filter_key'); // Filter key (newest, featured, price_low_high, price_high_low)

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');

            // Indexes
            $table->index('company_id');
            $table->index(['company_id', 'filter_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eco_filter_settings');
    }
};
