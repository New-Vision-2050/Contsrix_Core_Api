<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('safety_record_violation', 'action')) {
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('action', 50)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('safety_record_violation', 'action')) {
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->dropColumn('action');
            });
        }
    }
};
