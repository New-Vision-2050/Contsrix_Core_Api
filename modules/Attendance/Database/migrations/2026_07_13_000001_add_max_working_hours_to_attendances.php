<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot the constraint's max_working_hours (required NET regular hours) onto the
 * attendance row at clock-in, mirroring the existing max_over_time snapshot column.
 *
 * This is the authoritative "total working hours" target that drives auto clock-out:
 * a shift is auto-closed once the user has actually worked max_working_hours of net time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('max_working_hours', 8, 2)->nullable()->after('max_over_time');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('max_working_hours');
        });
    }
};
