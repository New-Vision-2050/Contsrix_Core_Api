<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * One `users` window cannot hold disjoint إجازة days: a later PATCH replaced
 * `manual_attendance_status_since`/`until` and the earlier day disappeared from
 * the calendar. Each granted range is now its own row (INV-18).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_manual_attendance_overrides')) {
            Schema::create('user_manual_attendance_overrides', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('user_id');
                $table->uuid('company_id')->nullable();
                $table->string('status');
                $table->date('starts_on')->nullable();
                $table->date('ends_on')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status', 'starts_on'], 'user_manual_att_overrides_user_status_start_idx');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'manual_attendance_status')) {
            return;
        }

        $now = now();

        DB::table('users')
            ->whereIn('manual_attendance_status', ['holiday', 'required_attendance'])
            ->orderBy('id')
            ->each(function (object $user) use ($now): void {
                $alreadyCopied = DB::table('user_manual_attendance_overrides')
                    ->where('user_id', $user->id)
                    ->exists();

                if ($alreadyCopied) {
                    return;
                }

                DB::table('user_manual_attendance_overrides')->insert([
                    'id' => (string) Str::uuid(),
                    'user_id' => $user->id,
                    'company_id' => $user->company_id ?? null,
                    'status' => $user->manual_attendance_status,
                    'starts_on' => $user->manual_attendance_status_since ?? null,
                    'ends_on' => $user->manual_attendance_status_until ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_manual_attendance_overrides');
    }
};
