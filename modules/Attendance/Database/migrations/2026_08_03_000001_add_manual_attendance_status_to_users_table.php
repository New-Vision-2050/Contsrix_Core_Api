<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('manual_attendance_status')->nullable()->after('status');
            $table->date('manual_attendance_status_since')->nullable()->after('manual_attendance_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['manual_attendance_status', 'manual_attendance_status_since']);
        });
    }
};
