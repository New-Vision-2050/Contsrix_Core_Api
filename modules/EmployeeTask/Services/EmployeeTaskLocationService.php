<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Attendance\Models\AttendanceConstraintLocation;
use Modules\EmployeeTask\Jobs\AutoCloseTaskIfOutOfLocationJob;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\EmployeeTask\Support\GeoDistance;
use Modules\User\Models\User;

final class EmployeeTaskLocationService
{
    private const DEFAULT_RADIUS_METRES    = 100;
    private const DEFAULT_THRESHOLD_MINUTES = 30;

    /**
     * Read the employee's main AttendanceConstraint at the moment the task is STARTED
     * and return the radius in metres to snapshot onto the task row.
     */
    public function snapshotRadiusFromConstraint(User $user): int
    {
        $constraint = $user->userProfessionalData?->attendanceConstraint;

        if (!$constraint) {
            return self::DEFAULT_RADIUS_METRES;
        }

        if (!empty($constraint->branch_locations)) {
            $first = $constraint->branch_locations[0] ?? null;
            if ($first && isset($first['radius'])) {
                return (int) $first['radius'];
            }
        }

        $tableLocation = AttendanceConstraintLocation::query()
            ->where('attendance_constraint_id', $constraint->id)
            ->orderBy('created_at')
            ->first();

        if ($tableLocation) {
            return (int) $tableLocation->radius;
        }

        $locationRules = $constraint->constraint_config['location_rules'] ?? [];
        if (!empty($locationRules['radius_meters'])) {
            return (int) $locationRules['radius_meters'];
        }

        return self::DEFAULT_RADIUS_METRES;
    }

    public function outOfRadiusThresholdMinutes(User $user): int
    {
        $constraint = $user->userProfessionalData?->attendanceConstraint;

        if (!$constraint) {
            return self::DEFAULT_THRESHOLD_MINUTES;
        }

        $enforcement = $constraint->constraint_config['location_rules']['out_of_radius_time_threshold']
            ?? $constraint->constraint_config['enforcement']['out_of_radius_time_threshold']
            ?? null;

        return $enforcement !== null ? (int) $enforcement : self::DEFAULT_THRESHOLD_MINUTES;
    }

    public function isWithinTaskRadius(EmployeeTaskRequest $task, float $lat, float $lng): bool
    {
        $radius = $task->radius_meters ?? self::DEFAULT_RADIUS_METRES;
        $distance = GeoDistance::metres(
            (float) $task->task_latitude,
            (float) $task->task_longitude,
            $lat,
            $lng,
        );

        return $distance <= $radius;
    }

    /**
     * Process a GPS ping from the mobile app.
     * Returns an array with in_location flag and, if out, minutes_out / threshold.
     * Dispatches AutoCloseTaskIfOutOfLocationJob when threshold is exceeded.
     */
    public function processLocationPing(
        EmployeeTaskRequest $task,
        float $lat,
        float $lng,
        string $timestamp,
        int $thresholdMinutes,
    ): array {
        $inLocation = $this->isWithinTaskRadius($task, $lat, $lng);
        $cacheKey   = 'task_out_of_location:' . $task->id;

        if ($inLocation) {
            Cache::forget($cacheKey);
            return ['in_location' => true];
        }

        $firstOutTimestamp = Cache::get($cacheKey);

        if (!$firstOutTimestamp) {
            Cache::put($cacheKey, $timestamp, now()->addMinutes($thresholdMinutes * 2));
            $firstOutTimestamp = $timestamp;
        }

        $firstOut       = CarbonImmutable::parse($firstOutTimestamp);
        $pingTime       = CarbonImmutable::parse($timestamp);
        $minutesOut     = (int) $firstOut->diffInMinutes($pingTime);

        if ($minutesOut >= $thresholdMinutes) {
            $closeAt = $firstOut->addMinutes($thresholdMinutes);

            AutoCloseTaskIfOutOfLocationJob::dispatch(
                taskId:     $task->id,
                companyId:  $task->company_id,
                closeAtIso: $closeAt->toIso8601String(),
            );

            Cache::forget($cacheKey);

            return [
                'in_location'           => false,
                'auto_close_triggered'  => true,
                'minutes_out'           => $minutesOut,
                'threshold_minutes'     => $thresholdMinutes,
            ];
        }

        return [
            'in_location'       => false,
            'auto_close_triggered' => false,
            'minutes_out'       => $minutesOut,
            'threshold_minutes' => $thresholdMinutes,
        ];
    }
}
