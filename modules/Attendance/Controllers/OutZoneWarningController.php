<?php

declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use BasePackage\Shared\Presenters\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Attendance\Models\Attendance;
use Modules\Attendance\Requests\ConfirmOutZoneLocationRequest;
use Modules\Attendance\Services\AttendanceConstraintService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\OutZoneClockOutWarningService;
use Modules\Attendance\Support\OutZoneClockOutWarning;
use Ramsey\Uuid\Uuid;

class OutZoneWarningController
{
    public function __construct(
        private AttendanceService $attendanceService,
        private AttendanceConstraintService $constraintService,
        private OutZoneClockOutWarningService $warningService,
    ) {}

    /**
     * GET /api/v1/attendance/out-zone-warning
     *
     * Mobile: if needs_location_confirm is true, show `message` and POST the
     * user's current GPS to confirm-location.
     */
    public function status(Request $request): JsonResponse
    {
        return Json::item($this->warningService->status($this->currentAttendance($request)));
    }

    /**
     * POST /api/v1/attendance/out-zone-warning/confirm-location
     * Body: { "latitude": 21.62, "longitude": 39.12 }
     */
    public function confirmLocation(ConfirmOutZoneLocationRequest $request): JsonResponse
    {
        $attendance = $this->currentAttendance($request);

        if ($attendance === null) {
            return Json::item($this->warningService->status(null));
        }

        $hadWarning = ! empty($attendance->out_zone_warning_at);
        $latitude = (float) $request->input('latitude');
        $longitude = (float) $request->input('longitude');
        $timezone = $attendance->timezone ?: config('app.timezone');

        $tracking = is_array($attendance->location_tracking) ? $attendance->location_tracking : [];
        $tracking[] = [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'timestamp' => now($timezone)->format('Y-m-d H:i:s'),
            'type' => 'track',
            'event' => 'out_zone_confirm',
            'is_mock' => false,
        ];
        $attendance->location_tracking = $tracking;
        $attendance->save();

        $this->constraintService->validateAttendance($attendance->fresh() ?? $attendance, [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        $fresh = $attendance->fresh() ?? $attendance;

        if ($fresh->clock_out_time) {
            return Json::item([
                'location_confirmed' => true,
                'still_outside' => true,
                'already_clocked_out' => true,
                'needs_location_confirm' => false,
                'message' => OutZoneClockOutWarning::ALREADY_CLOCKED_OUT,
                'clock_out_location' => is_array($fresh->clock_out_location) ? $fresh->clock_out_location : null,
            ]);
        }

        $warning = OutZoneClockOutWarning::payload($fresh);

        if ($warning !== null) {
            return Json::item(array_merge($warning, [
                'location_confirmed' => true,
                'still_outside' => true,
                'already_clocked_out' => false,
                'message' => OutZoneClockOutWarning::STILL_OUTSIDE,
            ]));
        }

        return Json::item([
            'location_confirmed' => true,
            'still_outside' => false,
            'already_clocked_out' => false,
            'needs_location_confirm' => false,
            'message' => $hadWarning
                ? OutZoneClockOutWarning::CONFIRMED_INSIDE
                : null,
        ]);
    }

    private function currentAttendance(Request $request): ?Attendance
    {
        $user = $request->user();
        if ($user === null) {
            return null;
        }

        return $this->attendanceService->getCurrentAttendance(Uuid::fromString((string) $user->id));
    }
}
