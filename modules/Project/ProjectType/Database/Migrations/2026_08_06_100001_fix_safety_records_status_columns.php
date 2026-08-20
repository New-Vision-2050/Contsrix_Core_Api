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

        // Ensure the column exists with the old default
        if (! Schema::hasColumn('safety_record_violation', 'status')) {
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('status', 30)
                    ->nullable()
                    ->default('not_applicable')
                    ->after('weight');
            });
        } else {
            // Restore the old default
            Schema::table('safety_record_violation', function (Blueprint $table) {
                $table->string('status', 30)
                    ->nullable()
                    ->default('not_applicable')
                    ->change();
            });
        }

        // Restore old values
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

    public function down(): void
    {
        if (! Schema::hasTable('safety_record_violation')) {
            return;
        }

        // Restore numeric default
        Schema::table('safety_record_violation', function (Blueprint $table) {
            $table->string('status', 30)
                ->nullable()
                ->default('0')
                ->change();
        });

        // Convert back to numeric values
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
};
