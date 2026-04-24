<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // start_time/clock_in_time are stored in branch TZ, so just extract the date portion.
        // created_at is a Laravel timestamp (UTC), so fall back carefully.
        DB::table('attendances')
            ->whereNull('business_date')
            ->orderBy('id')
            ->chunkById(1000, function ($rows) {
                foreach ($rows as $row) {
                    $tz = $row->timezone ?: config('app.timezone') ?: 'Asia/Riyadh';
                    $anchor = $row->start_time ?? $row->clock_in_time ?? $row->created_at;

                    if ($anchor === null) {
                        continue;
                    }

                    // start_time / clock_in_time stored in branch TZ — parse as-is.
                    // created_at is UTC — convert to branch TZ first.
                    if ($anchor === $row->created_at && $row->start_time === null && $row->clock_in_time === null) {
                        $date = Carbon::parse($anchor, 'UTC')->setTimezone($tz)->toDateString();
                    } else {
                        $date = Carbon::parse($anchor)->toDateString();
                    }

                    DB::table('attendances')
                        ->where('id', $row->id)
                        ->update(['business_date' => $date]);
                }
            });
    }

    public function down(): void
    {
        DB::table('attendances')->update(['business_date' => null]);
    }
};
