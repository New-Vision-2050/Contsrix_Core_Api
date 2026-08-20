<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'manual_attendance_status')) {
                $table->string('manual_attendance_status')->nullable();
            }

            if (! Schema::hasColumn('users', 'manual_attendance_status_since')) {
                $table->date('manual_attendance_status_since')->nullable();
            }

            if (! Schema::hasColumn('users', 'manual_attendance_status_until')) {
                $table->date('manual_attendance_status_until')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'manual_attendance_status_until')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('manual_attendance_status_until');
        });
    }
};
