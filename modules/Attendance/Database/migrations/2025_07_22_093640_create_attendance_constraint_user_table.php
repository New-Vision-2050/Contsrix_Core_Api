<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_constraint_user', function (Blueprint $table) {
            $table->uuid('attendance_constraint_id');
            $table->uuid('user_id');
            $table->timestamps();

            $table->foreign('attendance_constraint_id')->references('id')->on('attendance_constraints')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->primary(['attendance_constraint_id', 'user_id'], 'attendance_constraint_user_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_constraint_user');
    }
};
