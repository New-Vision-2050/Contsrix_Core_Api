<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('applied_attendance_constraints', function (Blueprint $table) {
            $table->id();
            $table->uuid('attendance_id');
            $table->uuid('constraint_id');
            $table->timestamps();

            $table->foreign('attendance_id')->references('id')->on('attendances')->onDelete('cascade');
            $table->foreign('constraint_id')->references('id')->on('attendance_constraints')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('applied_attendance_constraints');
    }
};
