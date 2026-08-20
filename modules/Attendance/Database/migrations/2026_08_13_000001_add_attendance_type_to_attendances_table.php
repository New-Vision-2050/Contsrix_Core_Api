<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'attendance_type')) {
                $table->string('attendance_type', 32)->nullable()->after('day_status');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances') || ! Schema::hasColumn('attendances', 'attendance_type')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('attendance_type');
        });
    }
};
