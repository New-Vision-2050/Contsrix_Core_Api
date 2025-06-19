<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBreakRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_records', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            // Relationship to attendance record
            $table->unsignedBigInteger('attendance_record_id');
            $table->foreign('attendance_record_id')->references('id')->on('attendance_records')->onDelete('cascade');
            
            // Break details
            $table->dateTime('break_start_time');
            $table->dateTime('break_end_time')->nullable();
            $table->string('break_type')->default('lunch'); // lunch, personal, etc.
            $table->integer('duration_minutes')->nullable();
            $table->text('note')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('tenant_id');
            $table->index('attendance_record_id');
            $table->index('break_start_time');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_records');
    }
}
