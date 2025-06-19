<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendanceSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_settings', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Work Hours Settings
            $table->time('work_start_time')->default('09:00:00');
            $table->time('work_end_time')->default('17:00:00');
            $table->integer('grace_period_minutes')->default(15);
            $table->integer('working_days_per_week')->default(5);
            $table->json('weekend_days')->default(json_encode([6, 0])); // Saturday, Sunday by default
            
            // Overtime Settings
            $table->boolean('enable_overtime')->default(true);
            $table->decimal('overtime_rate', 5, 2)->default(1.5);
            $table->integer('overtime_start_after_minutes')->default(0); // Start calculating overtime immediately after work hours
            
            // Break Settings
            $table->integer('break_time_minutes')->default(60); // Default 1 hour lunch break
            $table->integer('max_breaks_per_day')->default(2);
            
            // Leave Settings
            $table->integer('default_annual_leave_days')->default(21);
            $table->integer('default_sick_leave_days')->default(14);
            $table->boolean('allow_leave_carryover')->default(true);
            $table->integer('max_leave_carryover_days')->default(5);
            
            // Location Settings
            $table->boolean('enforce_location_based_attendance')->default(false);
            $table->decimal('location_accuracy_threshold', 10, 6)->default(0.1); // In kilometers
            $table->json('allowed_attendance_locations')->nullable();
            
            // Notification Settings
            $table->boolean('notify_manager_on_absence')->default(true);
            $table->boolean('notify_employee_on_overtime')->default(true);
            
            $table->timestamps();
            $table->unique('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_settings');
    }
}
