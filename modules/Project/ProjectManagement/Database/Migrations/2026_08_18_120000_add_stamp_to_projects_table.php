<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('projects') || Schema::hasColumn('projects', 'stamp')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('stamp')->nullable()->after('serial_number');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('projects') || ! Schema::hasColumn('projects', 'stamp')) {
            return;
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('stamp');
        });
    }
};
