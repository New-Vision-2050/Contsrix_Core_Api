<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendanceRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Employee reference - using management hierarchy for role/branch relationship
            $table->string('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('management_hierarchy_id')->nullable();
            $table->foreign('management_hierarchy_id')->references('id')->on('management_hierarchies')->onDelete('set null');

            // Clock in details
            $table->dateTime('clock_in_time')->nullable();
            $table->string('clock_in_ip_address')->nullable();
            $table->decimal('clock_in_latitude', 10, 7)->nullable();
            $table->decimal('clock_in_longitude', 10, 7)->nullable();
            $table->string('clock_in_device_info')->nullable();
            $table->text('clock_in_note')->nullable();
            $table->boolean('is_late_arrival')->default(false);
            $table->integer('late_minutes')->nullable();
            
            // Clock out details
            $table->dateTime('clock_out_time')->nullable();
            $table->string('clock_out_ip_address')->nullable();
            $table->decimal('clock_out_latitude', 10, 7)->nullable();
            $table->decimal('clock_out_longitude', 10, 7)->nullable();
            $table->string('clock_out_device_info')->nullable();
            $table->text('clock_out_note')->nullable();
            $table->boolean('is_early_departure')->default(false);
            $table->integer('early_departure_minutes')->nullable();
            
            // Work hours calculations
            $table->integer('total_work_minutes')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            
            // Status and flags
            $table->enum('status', ['active', 'completed', 'absent', 'half_day', 'on_leave'])->default('active');
            $table->boolean('is_manual_entry')->default(false);
            $table->string('created_by')->nullable();
            $table->text('remarks')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Index for performance
            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('clock_in_time');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_records');
    }
}
