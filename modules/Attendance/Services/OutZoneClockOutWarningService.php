<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Support\OutZoneClockOutWarning;

class OutZoneClockOutWarningService
{
    public function __construct(
        private ?AttendanceNotificationService $notifications = null,
    ) {}

    /**
     * First outside ping: store the warning so mobile shows confirm-location,
     * and send three FCM pushes. Later pings do not notify again. Does not
     * clock the employee out.
     */
    public function issue(Attendance $attendance): void
    {
        if (! $this->isConfirmEnabled()) {
            $this->clear($attendance);

            return;
        }

        if (! empty($attendance->out_zone_warning_at)) {
            return;
        }

        $tz = $attendance->timezone ?: date_default_timezone_get();
        $attendance->out_zone_warning_at = Carbon::now($tz)->format('Y-m-d H:i:s');

        if ($attendance->exists) {
            $attendance->save();
        }

        $this->sendConfirmNotifications($attendance);
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
        if (! $this->isConfirmEnabled()) {
            return [
                'needs_location_confirm' => false,
                'message' => null,
                'attendance_id' => $attendance?->id !== null ? (string) $attendance->id : null,
                'already_clocked_out' => (bool) $attendance?->clock_out_time,
            ];
        }

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

    private function sendConfirmNotifications(Attendance $attendance): void
    {
        $count = OutZoneClockOutWarning::NOTIFICATION_COUNT;
        try {
            $configured = config('attendance.out_zone_confirm_notification_count');
            if (is_numeric($configured) && (int) $configured > 0) {
                $count = (int) $configured;
            }
        } catch (\Throwable) {
            // keep default
        }

        try {
            $service = $this->notifications ?? app(AttendanceNotificationService::class);
            $service->notifyConfirmLocation($attendance, $count);
        } catch (\Throwable $e) {
            Log::error('Out-zone confirm notifications failed', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isConfirmEnabled(): bool
    {
        try {
            return (bool) config('attendance.out_zone_confirm_enabled', true);
        } catch (\Throwable) {
            return true;
        }
    }
}
