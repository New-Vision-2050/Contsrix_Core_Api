<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('safety_record_violation')) {
            return;
        }

        // 1. Ensure the column exists with the correct default
        if (! Schema::hasColumn('safety_record_violation', 'status')) {
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('status', 30)
                    ->nullable()
                    ->default('0')
                    ->after('weight');
            });
        } else {
            // Ensure the default is set to '0' for existing columns
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('status', 30)
                    ->nullable()
                    ->default('0')
                    ->change();
            });
        }

        // 2. Convert legacy textual statuses to numeric strings
        DB::table('safety_record_violation')
            ->where('status', 'violation_found')
            ->update(['status' => '-1']);

        DB::table('safety_record_violation')
            ->where('status', 'no_violation')
            ->update(['status' => '1']);

        DB::table('safety_record_violation')
            ->where(function ($query) {
                $query->where('status', 'not_applicable')
                     ->orWhereNull('status');
            })
            ->update(['status' => '0']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('safety_record_violation')) {
            return;
        }

        DB::table('safety_record_violation')
            ->where('status', '-1')
            ->update(['status' => 'violation_found']);

        DB::table('safety_record_violation')
            ->where('status', '1')
            ->update(['status' => 'no_violation']);

        DB::table('safety_record_violation')
            ->where(function ($query) {
                $query->where('status', '0')
                     ->orWhereNull('status');
            })
            ->update(['status' => 'not_applicable']);
    }
};
