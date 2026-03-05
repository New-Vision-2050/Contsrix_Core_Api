<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->unsignedInteger('max_over_time')->nullable()->after('constraint_config');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedInteger('max_over_time')->nullable()->after('overtime_hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->dropColumn('max_over_time');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('max_over_time');
        });
    }
};
