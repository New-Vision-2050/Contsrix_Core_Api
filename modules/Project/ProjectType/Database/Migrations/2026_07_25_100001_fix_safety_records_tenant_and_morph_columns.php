<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('safety_records')) {
            return;
        }

        if (! Schema::hasColumn('safety_records', 'company_id')) {
            Schema::table('safety_records', function (Blueprint $table) {
                $table->uuid('company_id')->nullable()->after('id');
                $table->foreign('company_id')->references('id')->on('companies')->nullOnDelete();
            });
        }

        // Convert uuid morphable_id to string so ProjectOrderPermit bigint ids work
        if (Schema::hasColumn('safety_records', 'morphable_id')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE safety_records MODIFY morphable_id VARCHAR(255) NOT NULL');
            } elseif (in_array($driver, ['pgsql', 'postgres'], true)) {
                DB::statement('ALTER TABLE safety_records ALTER COLUMN morphable_id TYPE VARCHAR(255) USING morphable_id::text');
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('safety_records')) {
            return;
        }

        if (Schema::hasColumn('safety_records', 'company_id')) {
            Schema::table('safety_records', function (Blueprint $table) {
                $table->dropForeign(['company_id']);
                $table->dropColumn('company_id');
            });
        }
    }
};
