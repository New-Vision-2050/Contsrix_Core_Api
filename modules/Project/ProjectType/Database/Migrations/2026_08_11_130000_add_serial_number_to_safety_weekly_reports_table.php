<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_weekly_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('safety_weekly_reports', 'serial_number')) {
                $table->string('serial_number', 30)->nullable()->after('id');
                $table->unique('serial_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('safety_weekly_reports', function (Blueprint $table) {
            if (Schema::hasColumn('safety_weekly_reports', 'serial_number')) {
                $table->dropUnique(['serial_number']);
                $table->dropColumn('serial_number');
            }
        });
    }
};
