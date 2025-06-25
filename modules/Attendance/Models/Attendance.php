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
use Modules\User\Models\User;
use Modules\Company\CompanyCore\Models\Company;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;

class Attendance extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
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
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'approved_by' => UuidCast::class,
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
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_PENDING_APPROVAL = 'pending_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * Get the user that owns the attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the company that owns the attendance record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
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
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('clock_in_time', [$startDate, $endDate]);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by company.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get active attendance (not clocked out yet).
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
                    ->whereNull('clock_out_time');
    }

    /**
     * Scope to get completed attendance records.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED)
                    ->whereNotNull('clock_out_time');
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
     * Calculate total work hours and update the model.
     */
    public function calculateWorkHours(): float
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            $this->total_work_hours = 0.0;
            $this->total_break_hours = 0.0;
            $this->overtime_hours = 0.0;
            return 0.0;
        }

        $clockIn = Carbon::parse($this->clock_in_time);
        $clockOut = Carbon::parse($this->clock_out_time);
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // Calculate break time if exists
        $breakMinutes = 0;
        if ($this->break_start_time && $this->break_end_time) {
            $breakStart = Carbon::parse($this->break_start_time);
            $breakEnd = Carbon::parse($this->break_end_time);
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $this->total_break_hours = round($breakMinutes / 60, 2);
        } else {
            $this->total_break_hours = 0.0;
        }

        // Calculate actual work hours (excluding breaks)
        $workMinutes = $totalMinutes - $breakMinutes;
        $workHours = round($workMinutes / 60, 2);
        $this->total_work_hours = $workHours;

        // Calculate overtime (assuming 8 hours standard)
        $standardHours = 8.0;
        $this->overtime_hours = $workHours > $standardHours ? round($workHours - $standardHours, 2) : 0.0;

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
     */
    public function getFormattedWorkDuration(): string
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return '0h 0m';
        }

        $totalMinutes = Carbon::parse($this->clock_out_time)->diffInMinutes(Carbon::parse($this->clock_in_time));
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
        $this->status = self::STATUS_COMPLETED;
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
        
        // Calculate work hours after ending the shift
        $this->calculateWorkHours();
        
        // Save changes
        return $this->save();
    }
}
