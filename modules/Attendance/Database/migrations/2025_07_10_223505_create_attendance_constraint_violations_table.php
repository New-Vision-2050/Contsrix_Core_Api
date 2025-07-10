<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration modifies the 'violation_details' column in the
     * 'attendance_constraints table to allow NULL values.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->json('constraint_config')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * This method reverts the 'violation_details' column back to being NOT NULL.
     * This is important for making your migrations reversible.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {

            $table->json('constraint_config')->nullable(false)->change();
        });
    }
};
