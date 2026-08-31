<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Jobs\ClockOutAfterOutZoneWarningJob;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Notifications\OutZoneClockOutWarningNotification;
use Modules\Attendance\Support\OutZoneClockOutWarning;
use Modules\User\Models\User;

class OutZoneClockOutWarningService
{
    /**
     * Start the extra 5-minute window once. Twilio is dialed in this request
     * (same as project site-status voice). Later GPS pings do not call again.
     */
    public function issue(Attendance $attendance): void
    {
        $isNew = empty($attendance->out_zone_warning_at);

        if ($isNew) {
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
            $this->rememberVoiceAttempt($attendance);

            return;
        }

        // Warning already stored (e.g. previous queued notify never dialed).
        // Place the call once on the next GPS ping after deploy.
        if ($this->claimVoiceRetry($attendance)) {
            $this->placeVoiceCall($attendance);
        }
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

        $this->forgetVoiceAttempt($attendance);
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

        if (! $user instanceof User) {
            Log::warning('Out-zone voice warning skipped: user not found', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
            ]);

            return;
        }

        $notification = new OutZoneClockOutWarningNotification();
        $fullPhone = $notification->buildInternationalPhoneNumber($user);

        if ($fullPhone === '' || ! str_starts_with($fullPhone, '+')) {
            Log::warning('Out-zone voice warning skipped: user has no international phone', [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'phone' => $user->phone,
                'phone_code' => $user->phone_code ?? null,
                'full_phone' => $fullPhone,
            ]);

            return;
        }

        try {
            // Call Twilio in-process. Do not use $user->notify() with ShouldQueue.
            $sid = $notification->toVoice($user)->send();

            if ($sid === false || $sid === null || $sid === '') {
                Log::error('Out-zone voice warning: Twilio did not start a call', [
                    'attendance_id' => $attendance->id,
                    'user_id' => $user->id,
                    'full_phone' => $fullPhone,
                ]);

                return;
            }

            Log::info('Out-zone voice warning: call started', [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'sid' => $sid,
                'full_phone' => $fullPhone,
            ]);
        } catch (\Throwable $e) {
            Log::error('Out-zone voice warning failed', [
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function voiceAttemptCacheKey(Attendance $attendance): ?string
    {
        $id = (string) ($attendance->id ?? '');

        return $id === '' ? null : 'attendance:out-zone-voice:' . $id;
    }

    private function rememberVoiceAttempt(Attendance $attendance): void
    {
        $key = $this->voiceAttemptCacheKey($attendance);
        if ($key === null) {
            return;
        }

        try {
            Cache::put($key, 1, now()->addMinutes(30));
        } catch (\Throwable) {
            // Cache is optional; missing table must not block the warning.
        }
    }

    private function claimVoiceRetry(Attendance $attendance): bool
    {
        $key = $this->voiceAttemptCacheKey($attendance);
        if ($key === null) {
            return false;
        }

        try {
            return Cache::add($key, 1, now()->addMinutes(30));
        } catch (\Throwable) {
            return false;
        }
    }

    private function forgetVoiceAttempt(Attendance $attendance): void
    {
        $key = $this->voiceAttemptCacheKey($attendance);
        if ($key === null) {
            return;
        }

        try {
            Cache::forget($key);
        } catch (\Throwable) {
            // ignore
        }
    }
}
