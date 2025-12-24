<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Casts\UuidCast;
use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;
use Modules\Attendance\Models\AttendanceBreak;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Modules\UserInfo\UserProfessionalData\Models\UserProfessionalData;

/**
 * @property string $id
 * @property string $user_id
 * @property string $company_id
 * @property Carbon|null $clock_in_time
 * @property Carbon|null $clock_out_time
 * @property float $total_work_hours
 * @property float $total_break_hours
 * @property float $overtime_hours
 * @property bool $is_late
 * @property bool $is_early_departure
 * @property int $late_minutes
 * @property int $early_departure_minutes
 * @property string $status
 * @property string|null $approved_by
 * @property Carbon|null $approved_at
 * @property array|null $clock_in_location
 * @property array|null $clock_out_location
 * @property string|null $notes
 * @property string|null $ip_address
 * @property string|null $timezone
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 * @property-read Company $company
 * @property-read User|null $approvedBy
 * @property-read Collection|AttendanceBreak[] $breaks
 * @property-read AttendanceConstraint|null $attendanceConstraint
 */
class Attendance extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    // use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'attendances';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'clock_in_time',
        'clock_out_time',
        'break_start_time',
        'break_end_time',
        'total_work_hours',
        'total_break_hours',
        'overtime_hours',
        'is_late',
        'is_absent',
        'is_holiday',
        'is_early_departure',
        'late_minutes',
        'early_departure_minutes',
        'status',
        'notes',
        'clock_in_location',
        'clock_out_location',
        'ip_address',
        'user_agent',
        'approved_by',
        'approved_at',
        'verification_data',
        'location_tracking',
        'timezone',
        'start_time',
        'end_time',
        'day_status',
        'date',
    ];

    protected $casts = [
        'id' =>'string',
        'user_id' => 'string',
        'company_id' => 'string',
        'approved_by' => 'string',
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
        'approved_at' => 'datetime',
        'total_work_hours' => 'decimal:2',
        'total_break_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
        'late_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean',
        'clock_in_location' => 'array',
        'clock_out_location' => 'array',
        'verification_data' => 'array',
        'location_tracking' => 'array',
    ];

    protected $dates = [
        'clock_in_time',
        'clock_out_time',
        'break_start_time',
        'break_end_time',
        'approved_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Status constants
    const STATUS_WAITING = 'waiting';  // New status for attendance records waiting for user to arrive
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Valid status transitions
     */
    private const STATUS_TRANSITIONS = [
        self::STATUS_WAITING => [
            self::STATUS_ACTIVE,
            self::STATUS_COMPLETED,  // Can transition directly to completed (absent)
        ],
        self::STATUS_ACTIVE => [
            self::STATUS_COMPLETED,
            self::STATUS_PENDING_APPROVAL,
        ],
        self::STATUS_COMPLETED => [
            self::STATUS_PENDING_APPROVAL,
        ],
        self::STATUS_PENDING_APPROVAL => [
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ],
    ];

    /**
     * Validate status transition
     */
    public function validateStatusTransition(string $newStatus): void
    {
        if (!in_array($newStatus, array_merge(...self::STATUS_TRANSITIONS), true)) {
            throw new \InvalidArgumentException('Invalid status');
        }

        if (!isset(self::STATUS_TRANSITIONS[$this->status]) ||
            !in_array($newStatus, self::STATUS_TRANSITIONS[$this->status], true)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$this->status} to {$newStatus}"
            );
        }
    }

    public function getClockInTimeAttribute($value)
    {
        if ($this->timezone && $value) {
            return Carbon::parse($value, timezone: 'UTC')->setTimezone($this->timezone);
        }
        return $value;
    }

    public function getClockOutTimeAttribute($value)
    {
        if ($this->timezone && $value) {
            return Carbon::parse($value, 'UTC')->setTimezone($this->timezone);
        }
        return $value;
    }

    public function getStartTimeAttribute($value)
    {
        if ($this->timezone && $value) {
            return Carbon::parse($value, 'UTC')->setTimezone($this->timezone);
        }
        return $value;
    }

    public function getEndTimeAttribute($value)
    {
        if ($this->timezone && $value) {
            return Carbon::parse($value, 'UTC')->setTimezone($this->timezone);
        }
        return $value;
    }
    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company that owns the attendance record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who approved this attendance record.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Alias for approver relationship
     */
    public function approvedBy(): BelongsTo
    {
        return $this->approver();
    }

    /**
     * Get all breaks for this attendance record.
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * Get the currently active break, if any.
     */
    public function activeBreak(): ?AttendanceBreak
    {
        return $this->breaks()->whereNotNull('start_time')->whereNull('end_time')->first();
    }

    /**
     * Get all completed breaks for this attendance record.
     */
    public function completedBreaks(): Collection
    {
        return $this->breaks()->whereNotNull('start_time')->whereNotNull('end_time')->get();
    }

    /**
     * Check if the employee is currently on break.
     */
    public function isOnBreak(): bool
    {
        // Check if there's an active break in the breaks relationship
        $activeBreak = $this->activeBreak();
        if ($activeBreak) {
            return true;
        }

        // No longer check legacy break fields
        return false;
    }

    /**
     * Calculate total break duration in minutes.
     */
    public function calculateTotalBreakMinutes(): int
    {
        $totalMinutes = 0;

        // Add minutes from completed breaks
        foreach ($this->completedBreaks() as $break) {
            // If duration_minutes is not set, calculate it from start/end time
            if ($break->duration_minutes === null && $break->start_time && $break->end_time) {
                $break->calculateDuration();
            }
            $totalMinutes += $break->duration_minutes ?? 0;
        }

        return $totalMinutes;
    }

    /**
     * Calculate total break hours.
     */
    public function calculateTotalBreakHours(): float
    {
        $hours = round($this->calculateTotalBreakMinutes() / 60, 2);
        $this->total_break_hours = $hours;
        $this->save();
        return $hours;
    }

    /**
     * Update total break hours in the model.
     */
    public function updateTotalBreakHours(): self
    {
        $this->total_break_hours = $this->calculateTotalBreakHours();
        $this->save();
        return $this;
    }

    /**
     * Validate attendance times
     */
    public function validateTimes(): void
    {
        if ($this->clock_in_time && $this->clock_out_time &&
            Carbon::parse($this->clock_out_time)->lt(Carbon::parse($this->clock_in_time))) {
            throw new \InvalidArgumentException('Clock out time cannot be before clock in time');
        }

        foreach ($this->breaks as $break) {
            if ($break->start_time && $break->end_time &&
                Carbon::parse($break->end_time)->lt(Carbon::parse($break->start_time))) {
                throw new \InvalidArgumentException('Break end time cannot be before start time');
            }

            if ($break->start_time && $this->clock_in_time &&
                Carbon::parse($break->start_time)->lt(Carbon::parse($this->clock_in_time))) {
                throw new \InvalidArgumentException('Break cannot start before clock in time');
            }

            if ($break->end_time && $this->clock_out_time &&
                Carbon::parse($break->end_time)->gt(Carbon::parse($this->clock_out_time))) {
                throw new \InvalidArgumentException('Break cannot end after clock out time');
            }
        }
    }

    /**
     * Validate location data
     */
    public function validateLocation(): void
    {
        if ($this->clock_in_location) {
            if (!is_array($this->clock_in_location) ||
                !isset($this->clock_in_location['latitude']) ||
                !isset($this->clock_in_location['longitude'])) {
                throw new \InvalidArgumentException('Invalid clock in location format');
            }
        }

        if ($this->clock_out_location) {
            if (!is_array($this->clock_out_location) ||
                !isset($this->clock_out_location['latitude']) ||
                !isset($this->clock_out_location['longitude'])) {
                throw new \InvalidArgumentException('Invalid clock out location format');
            }
        }
    }

    /**
     * Validate IP address
     */
    public function validateIp(): void
    {
        if ($this->ip_address && !filter_var($this->ip_address, FILTER_VALIDATE_IP)) {
            throw new \InvalidArgumentException('Invalid IP address format');
        }
    }

    /**
     * Validate user agent
     */
    public function validateUserAgent(): void
    {
        if ($this->user_agent && strlen($this->user_agent) > 255) {
            throw new \InvalidArgumentException('User agent string too long');
        }
    }

    /**
     * Validate all data before saving
     */
    public function validate(): void
    {
        $this->validateTimes();
        $this->validateLocation();
        $this->validateIp();
        $this->validateUserAgent();
    }

    /**
     * Scope to filter by date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|Carbon $startDate
     * @param string|Carbon $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereDate('clock_in_time', '>=', $startDate)
            ->whereDate('clock_in_time', '<=', $endDate);
    }

    /**
     * Scope to filter by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by company.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $companyId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get active attendance (not clocked out yet).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)->whereNull('clock_out_time');
    }

    /**
     * Scope to get completed attendance records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)->whereNotNull('clock_out_time');
    }

    /**
     * Check if the attendance record is currently active (clocked in but not out).
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && is_null($this->clock_out_time);
    }

    /**
     * Check if the attendance record is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED && !is_null($this->clock_out_time);
    }

    /**
     * Check if the attendance record is pending approval.
     */
    public function isPendingApproval(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    /**
     * Check if the attendance record is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /**
     * Check if the attendance record is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Check if the clock-in time is late compared to the scheduled start time or last clock-in.
     * For first clock-in in a period, lateness is calculated from scheduled start time.
     * For subsequent clock-ins in the same period, lateness is calculated from the last clock-in time.
     *
     * @return $this
     */
    public function checkLateness(): self
    {
        if (!$this->clock_in_time) {
            $this->is_late = false;
            $this->late_minutes = 0;
            return $this;
        }

        $timezone = getTimeZoneByRequest() ?? config('app.timezone');

        try {
            $clockIn = Carbon::parse($this->clock_in_time)->setTimezone($timezone);
            $clockInDate = $clockIn->format('Y-m-d');

            $constraintService = app(\Modules\Attendance\Services\AttendanceConstraintService::class);
            $user = $this->user;
            $config = $constraintService->getTodaysWorkRulesForUser($user);


            $rules = $config['lateness_rules'] ?? [];
            $latenessPeriod = (int)($rules['lateness_period'] ?? 0);
            $latenessUnit = $rules['lateness_unit'] ?? 'minute';

            $gracePeriodMinutes = $this->convertToMinutes($latenessPeriod, $latenessUnit);

            if ($gracePeriodMinutes <= 0) {
                $gracePeriodMinutes = (int)($rules['grace_period_minutes'] ?? 0);
            }

            $previousAttendances = self::where('user_id', $this->user_id)
                ->whereDate('clock_in_time', $clockInDate)
                ->where('id', '!=', $this->id)
                ->orderBy('clock_in_time', 'desc')
                ->get();

            // Always get the scheduled start time for the period
            $scheduledStartTimeString = $this->start_time ?? $this->user->start_time ?? '09:00';
            if ($scheduledStartTimeString instanceof Carbon) {
                $scheduledStartTimeString = $scheduledStartTimeString->format('H:i');
            }

            $scheduledStartTime = $clockIn->copy()->setTimeFromTimeString($scheduledStartTimeString);
            $latestAllowedArrival = $scheduledStartTime->copy()->addMinutes($gracePeriodMinutes);

            // Always calculate is_late based on scheduled period time
            $this->is_late = $clockIn->gt($latestAllowedArrival);

            if ($previousAttendances->isEmpty()) {
                // First clock-in: late_minutes is calculated from scheduled start time
                $this->late_minutes = $this->is_late ? $latestAllowedArrival->diffInMinutes($clockIn) : 0;
            } else {
                // Subsequent clock-in: check if in same period
                $lastAttendance = $previousAttendances->first();
                $lastClockInTime = Carbon::parse($lastAttendance->clock_in_time)->setTimezone($timezone);

                $scheduledEndTimeString = $this->end_time ?? $this->user->end_time ?? '17:00';
                if ($scheduledEndTimeString instanceof Carbon) {
                    $scheduledEndTimeString = $scheduledEndTimeString->format('H:i');
                }

                $periodStart = $clockIn->copy()->setTimeFromTimeString($scheduledStartTimeString);
                $periodEnd = $clockIn->copy()->setTimeFromTimeString($scheduledEndTimeString);

                // Check if the last clock-in was in the same period
                $lastClockInPeriodStart = $lastClockInTime->copy()->setTimeFromTimeString($scheduledStartTimeString);
                $lastClockInPeriodEnd = $lastClockInTime->copy()->setTimeFromTimeString($scheduledEndTimeString);

                $sameDay = $lastClockInTime->isSameDay($clockIn);
                $samePeriod = $sameDay &&
                             (($lastClockInTime->between($lastClockInPeriodStart, $lastClockInPeriodEnd) &&
                               $clockIn->between($periodStart, $periodEnd)));

                if ($samePeriod) {
                    // Same period - calculate late_minutes from last clock-in time
                    $lastClockInWithGrace = $lastClockInTime->copy()->addMinutes($gracePeriodMinutes);
                    $this->late_minutes = $clockIn->gt($lastClockInWithGrace) ?
                                         $lastClockInWithGrace->diffInMinutes($clockIn) : 0;
                } else {
                    $this->late_minutes = $this->is_late ? $latestAllowedArrival->diffInMinutes($clockIn) : 0;
                }
            }

            $this->save();

        } catch (\Exception $e) {
            Log::error('Error checking lateness: ' . $e->getMessage(), [
                'clock_in_time' => $this->clock_in_time,
                'attendance_id' => $this->id,
                'trace' => $e->getTraceAsString()
            ]);

            $this->is_late = false;
            $this->late_minutes = 0;
        }

        return $this;
    }

    /**
     * Converts a time value from the specified unit to minutes.
     *
     * @param int $value The time value to convert
     * @param string $unit The unit of the time value ('minute', 'hour', or 'day')
     * @return int The equivalent time value in minutes
     */
    private function convertToMinutes(int $value, string $unit): int
    {
        switch (strtolower($unit)) {
            case 'hour':
                return $value * 60;
            case 'day':
                return $value * 24 * 60;
            case 'minute':
            default:
                return $value;
        }
    }

    /**
     * Calculate total work hours and update the model.
     */
    public function calculateWorkHours(): float
    {
        // 1. Pre-condition check
        if (!$this->clock_in_time || !$this->clock_out_time) {
            $this->total_work_hours = 0.0;
            $this->total_break_hours = 0.0;
            $this->overtime_hours = 0.0;
            $this->is_early_departure = false;
            $this->early_departure_minutes = 0;
            $this->save();
            return 0.0;
        }

        // 2. Timezone setup
        $timezone = getTimeZoneByRequest() ?? config('app.timezone');
        try {
            $clockIn = Carbon::parse($this->clock_in_time)->setTimezone($timezone);
            $clockOut = Carbon::parse($this->clock_out_time)->setTimezone($timezone);
        } catch (\Exception $e) {
            Log::error('Error parsing clock_in_time or clock_out_time: ' . $e->getMessage(), [
                'clock_in_time' => $this->clock_in_time,
                'clock_out_time' => $this->clock_out_time,
                'attendance_id' => $this->id,
            ]);

            // If parsing fails, reset and save.
            $this->total_work_hours = 0.0;
            $this->total_break_hours = 0.0;
            $this->overtime_hours = 0.0;
            $this->is_early_departure = false;
            $this->early_departure_minutes = 0;
            $this->save();
            return 0.0;
        }

        // 3. Validity check
        if ($clockOut->isBefore($clockIn)) {
            // If data is invalid, reset and save.
            $this->total_work_hours = 0.0;
            $this->total_break_hours = 0.0;
            $this->overtime_hours = 0.0;
            $this->is_early_departure = false;
            $this->early_departure_minutes = 0;
            $this->save();
            return 0.0;
        }

        // --- CALCULATIONS ---

        // 4. Calculate Total Break Duration
        $breakMinutes = $this->calculateTotalBreakMinutes();
        $this->total_break_hours = round($breakMinutes / 60, 2);

        // 5. Calculate Net Work Duration
        $totalGrossMinutes = $this->clock_in_time->diffInMinutes($this->clock_out_time, false);
        $workMinutes = $totalGrossMinutes - $breakMinutes;
        $workHours = $workMinutes > 0 ? round($workMinutes / 60, 2) : 0.0;
        $this->total_work_hours = $workHours;

        // 6. Calculate Overtime
        $standardHours = 8.0;
        if ($workHours > $standardHours) {
            $this->overtime_hours = round($workHours - $standardHours, 2);
        } else {
            $this->overtime_hours = 0.0;
        }

        // 7. Calculate Late and Early Departure
        // *** IMPROVED: Get scheduled start and end times from user/company settings ***
        $scheduledStartCandidate = $this->start_time ?? $this->user->start_time ?? '09:00';
        $scheduledEndCandidate = $this->end_time ?? $this->user->end_time ?? '17:00';

        // Normalize possible Carbon or datetime strings to plain time strings (H:i:s)
        if ($scheduledStartCandidate instanceof \Carbon\Carbon) {
            $scheduledStartTimeString = $scheduledStartCandidate->format('H:i:s');
        } else {
            $scheduledStartTimeString = (string) $scheduledStartCandidate;
            if (is_string($scheduledStartCandidate) && (str_contains($scheduledStartCandidate, 'T') || str_contains($scheduledStartCandidate, ' '))) {
                $scheduledStartTimeString = \Carbon\Carbon::parse($scheduledStartCandidate)->format('H:i:s');
            }
        }

        if ($scheduledEndCandidate instanceof \Carbon\Carbon) {
            $scheduledEndTimeString = $scheduledEndCandidate->format('H:i:s');
        } else {
            $scheduledEndTimeString = (string) $scheduledEndCandidate;
            if (is_string($scheduledEndCandidate) && (str_contains($scheduledEndCandidate, 'T') || str_contains($scheduledEndCandidate, ' '))) {
                $scheduledEndTimeString = \Carbon\Carbon::parse($scheduledEndCandidate)->format('H:i:s');
            }
        }

        try {
            $scheduledStartTime = $clockIn->copy()->setTimeFromTimeString($scheduledStartTimeString);
            $scheduledEndTime = $clockIn->copy()->setTimeFromTimeString($scheduledEndTimeString);
        } catch (\Exception $e) {
            // Fallback to default values if parsing fails
            $scheduledStartTime = $clockIn->copy()->setHour(9)->setMinute(0)->setSecond(0);
            $scheduledEndTime = $clockIn->copy()->setHour(17)->setMinute(0)->setSecond(0);
        }

        // Only set lateness if it hasn't been set during clock-in
        // This preserves the lateness values set by checkLateness() during clock-in
        if ($this->is_late === null) {
            $this->is_late = $clockIn->gt($scheduledStartTime);
            $this->late_minutes = $this->is_late ? $scheduledStartTime->diffInMinutes($clockIn) : 0;
        }

        $this->is_early_departure = $clockOut->lt($scheduledEndTime);
        $this->early_departure_minutes = $this->is_early_departure ? $clockOut->diffInMinutes($scheduledEndTime) : 0;

        $this->validate();

        $this->save();
        return $workHours;
    }
    /**
     * Calculate overtime hours based on standard work hours.
     */
    public function calculateOvertimeHours(float $standardHours = 8.0): float
    {
        $workHours = $this->calculateWorkHours();
        return $workHours > $standardHours ? round($workHours - $standardHours, 2) : 0.0;
    }

    /**
     * Get formatted work duration.
     *
     * @return string
     */
    public function getFormattedWorkDuration(): string
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return '0h 0m';
        }

        // Get company's timezone
        $timezone = $this->company?->timezone ?? config('app.timezone');

        // Parse times with timezone
        $clockIn = Carbon::parse($this->clock_in_time)->setTimezone($timezone);
        $clockOut = Carbon::parse($this->clock_out_time)->setTimezone($timezone);

        // Calculate break minutes
        $breakMinutes = $this->calculateTotalBreakMinutes();

        // Calculate total minutes excluding breaks
        $totalMinutes = $clockOut->diffInMinutes($clockIn) - $breakMinutes;
        $totalMinutes = max(0, $totalMinutes); // Ensure we don't have negative minutes

        $hours = intval($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        return "{$hours}h {$minutes}m";
    }

    /**
     * End the current shift automatically based on constraint enforcement
     *
     * @param string $method The method used to end the shift (e.g., 'auto_radius_enforcement', 'auto_time_limit')
     * @param string $notes Additional notes about why the shift was ended
     * @param bool $markAbsent Whether to mark the day as absent in attendance records
     * @return bool Whether the shift was successfully ended
     */
    public function endShift(string $method, string $notes, bool $markAbsent = false): bool
    {
        if (!$this->isActive()) {
            return false; // Cannot end an inactive or already completed shift
        }

        // Set clock out time to current time
        $this->clock_out_time = Carbon::now();

        try {
            $this->validateStatusTransition(self::STATUS_COMPLETED);
            $this->status = self::STATUS_COMPLETED;
        } catch (\InvalidArgumentException $e) {
            // Log the error but continue with the shift ending
            \Log::warning("Invalid status transition when ending shift: {$e->getMessage()}");
            // Force the status to completed
            $this->status = self::STATUS_COMPLETED;
        }

        $this->shift_end_method = $method;

        // Append notes with timestamp
        $timestamp = Carbon::now()->format('Y-m-d H:i:s');
        $existingNotes = $this->notes ? $this->notes . "\n\n" : '';
        $this->notes = $existingNotes . "[{$timestamp}] Auto-ended: {$notes}";

        // If configured to mark day as absent
        if ($markAbsent) {
            $this->is_absent = true;
            $this->absence_reason = "Automatically marked absent due to constraint violation: {$method}";
        }

        // End any active breaks
        $activeBreak = $this->activeBreak();
        if ($activeBreak) {
            $activeBreak->end_time = Carbon::now();
            $activeBreak->calculateDuration();
            $activeBreak->save();
        }

        // Calculate break hours first
        $this->updateTotalBreakHours();

        // Calculate work hours after ending the shift
        $this->calculateWorkHours();

        // Save changes
        return $this->save();
    }
    public function appliedAttendanceConstraint()
    {
        return $this->hasOne(AppliedAttendanceConstraint::class, 'attendance_id', 'id');
    }
    public function professionalData()
    {
        return $this->hasOne(UserProfessionalData::class, 'user_id', 'user_id');
    }
}
