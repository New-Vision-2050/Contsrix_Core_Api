<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('safety_records', function (Blueprint $table) {
            if (! Schema::hasColumn('safety_records', 'inspection_date')) {
                $table->date('inspection_date')->nullable()->after('time');
            }

            if (! Schema::hasColumn('safety_records', 'inspection_time')) {
                $table->time('inspection_time')->nullable()->after('inspection_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('safety_records', function (Blueprint $table) {
            if (Schema::hasColumn('safety_records', 'inspection_time')) {
                $table->dropColumn('inspection_time');
            }

            if (Schema::hasColumn('safety_records', 'inspection_date')) {
                $table->dropColumn('inspection_date');
            }
        });
    }
};
