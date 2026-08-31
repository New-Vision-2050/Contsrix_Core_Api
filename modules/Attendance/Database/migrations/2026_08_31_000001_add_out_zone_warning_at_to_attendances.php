<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the employee stays outside all geofences past out_zone_minutes, we warn
 * with a voice call and wait 5 more minutes before auto clock-out. This column
 * is the start of that extra window. Null means no pending warning.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'out_zone_warning_at')) {
                $table->timestamp('out_zone_warning_at')->nullable()->after('shift_end_method');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'out_zone_warning_at')) {
                $table->dropColumn('out_zone_warning_at');
            }
        });
    }
};
