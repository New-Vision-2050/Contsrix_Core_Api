<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class 2025_08_18_100500_create_leave_policies_table Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->string('name');
            $table->integer('total_days')->nullable();
            $table->string('day_type')->nullable();
            $table->boolean('is_rollover_allowed')->default(false);
            $table->integer('max_days_per_request')->nullable();
            $table->string('upgrade_condition')->nullable();
            $table->boolean('is_allow_half_day')->default(false);
            $table->timestamps();

            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
    }
};
