<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Jobs\ClockOutAfterOutZoneWarningJob;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Notifications\OutZoneClockOutWarningNotification;
use Modules\Attendance\Support\OutZoneClockOutWarning;
use Modules\User\Models\User;

class OutZoneClockOutWarningService
{
    /**
     * Start the extra 5-minute window once. Later GPS pings do not call again.
     */
    public function issue(Attendance $attendance): void
    {
        if (! empty($attendance->out_zone_warning_at)) {
            return;
        }

        $tz = $attendance->timezone ?: date_default_timezone_get();
        $attendance->out_zone_warning_at = Carbon::now($tz)->format('Y-m-d H:i:s');

        if ($attendance->exists) {
            $attendance->save();
        }

        $companyId = (string) ($attendance->company_id ?? '');
        if ($companyId !== '' && $attendance->id) {
            ClockOutAfterOutZoneWarningJob::dispatch(
                (string) $attendance->id,
                $companyId,
            )->delay(now()->addMinutes(OutZoneClockOutWarning::GRACE_MINUTES));
        }

        $this->placeVoiceCall($attendance);
    }

    public function clear(Attendance $attendance): void
    {
        if (empty($attendance->out_zone_warning_at)) {
            return;
        }

        $attendance->out_zone_warning_at = null;

        if ($attendance->exists) {
            $attendance->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function status(?Attendance $attendance): array
    {
        $payload = OutZoneClockOutWarning::payload($attendance);

        if ($payload === null) {
            return [
                'needs_location_confirm' => false,
                'message' => $attendance === null
                    ? OutZoneClockOutWarning::NOT_CLOCKED_IN
                    : ($attendance->clock_out_time
                        ? OutZoneClockOutWarning::ALREADY_CLOCKED_OUT
                        : null),
                'attendance_id' => $attendance?->id !== null ? (string) $attendance->id : null,
                'already_clocked_out' => (bool) $attendance?->clock_out_time,
            ];
        }

        return $payload;
    }

    private function placeVoiceCall(Attendance $attendance): void
    {
        $user = $attendance->relationLoaded('user') && $attendance->user
            ? $attendance->user
            : User::query()->find($attendance->user_id);

        if (! $user instanceof User || trim((string) $user->phone) === '') {
            Log::warning('Out-zone voice warning skipped: user has no phone', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
            ]);

            return;
        }

        try {
            $user->notify(new OutZoneClockOutWarningNotification());
        } catch (\Throwable $e) {
            Log::error('Out-zone voice warning failed', [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
