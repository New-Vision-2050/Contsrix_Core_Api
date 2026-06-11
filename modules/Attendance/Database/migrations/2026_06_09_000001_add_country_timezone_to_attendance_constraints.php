<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->unsignedBigInteger('country_id')->nullable()->after('notes');
            $table->unsignedBigInteger('time_zone_id')->nullable()->after('country_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_constraints', function (Blueprint $table) {
            $table->dropColumn(['country_id', 'time_zone_id']);
        });
    }
};
