<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            
            // Basic information
            $table->json('name'); // Translatable field
            $table->json('description')->nullable(); // Translatable field
            
            // Leave policy settings
            $table->integer('max_days_per_year')->default(0);
            $table->integer('max_consecutive_days')->default(0);
            $table->integer('min_notice_days')->default(0);
            
            // Leave type properties
            $table->boolean('is_paid')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('requires_attachment')->default(false);
            
            // UI and organization
            $table->string('color_code', 7)->default('#4CAF50'); // Hex color
            $table->integer('sort_order')->default(0);
            
            // Accrual settings
            $table->decimal('accrual_rate', 8, 2)->default(0); // Days per month
            $table->integer('carry_over_limit')->default(0);
            
            // Blackout periods (JSON array of date ranges)
            $table->json('blackout_periods')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Foreign key constraints
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leave_types');
    }
};
