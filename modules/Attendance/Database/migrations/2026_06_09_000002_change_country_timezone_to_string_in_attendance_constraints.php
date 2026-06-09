<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->string('country_id', 36)->nullable()->change();
            $table->string('time_zone_id', 36)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->change();
            $table->unsignedBigInteger('time_zone_id')->nullable()->change();
        });
    }
};
