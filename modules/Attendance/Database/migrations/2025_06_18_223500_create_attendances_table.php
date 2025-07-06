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
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('company_id');

            // Clock in/out times
            $table->timestamp('clock_in_time');
            $table->timestamp('clock_out_time')->nullable();

            // Break times
            $table->timestamp('break_start_time')->nullable();
            $table->timestamp('break_end_time')->nullable();

            // Calculated hours
            $table->decimal('total_work_hours', 8, 2)->default(0);
            $table->decimal('total_break_hours', 8, 2)->default(0);
            $table->decimal('overtime_hours', 8, 2)->default(0);

            // Late/early tracking
            $table->boolean('is_late')->default(false);
            $table->boolean('is_early_departure')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);

            // Status and approval
            $table->enum('status', ['active', 'completed', 'pending_approval', 'approved', 'rejected'])
                  ->default('active');
            $table->text('notes')->nullable();

            // Location tracking
            $table->json('clock_in_location')->nullable();
            $table->json('clock_out_location')->nullable();

            // Audit fields
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            // Approval tracking
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            // Indexes for performance
            $table->index(['user_id', 'clock_in_time']);
            $table->index(['company_id', 'clock_in_time']);
            $table->index(['status']);
            $table->index(['clock_in_time']);
            $table->index(['is_late']);
            $table->index(['is_early_departure']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
