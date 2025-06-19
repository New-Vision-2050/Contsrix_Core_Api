<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;
use Modules\User\Models\User;
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;

class AttendanceRecord extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;
    use SoftDeletes;

    protected $table = 'attendance_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'management_hierarchy_id',
        'clock_in_time',
        'clock_in_ip_address',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_device_info',
        'clock_in_note',
        'is_late_arrival',
        'late_minutes',
        'clock_out_time',
        'clock_out_ip_address',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_device_info',
        'clock_out_note',
        'is_early_departure',
        'early_departure_minutes',
        'total_work_minutes',
        'break_minutes',
        'overtime_minutes',
        'status',
        'is_manual_entry',
        'created_by',
        'remarks',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'is_late_arrival' => 'boolean',
        'is_early_departure' => 'boolean',
        'is_manual_entry' => 'boolean',
        'clock_in_latitude' => 'float',
        'clock_in_longitude' => 'float',
        'clock_out_latitude' => 'float',
        'clock_out_longitude' => 'float',
    ];

    /**
     * Get the user that owns the attendance record.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the management hierarchy associated with the attendance record.
     */
    public function managementHierarchy()
    {
        return $this->belongsTo(ManagementHierarchy::class, 'management_hierarchy_id');
    }

    /**
     * Get the break records for the attendance record.
     */
    public function breakRecords()
    {
        return $this->hasMany(BreakRecord::class);
    }

    /**
     * Calculate total work hours (decimal)
     */
    public function getTotalWorkHoursAttribute()
    {
        return $this->total_work_minutes ? round($this->total_work_minutes / 60, 2) : null;
    }

    /**
     * Calculate overtime hours (decimal)
     */
    public function getOvertimeHoursAttribute()
    {
        return $this->overtime_minutes ? round($this->overtime_minutes / 60, 2) : null;
    }

    /**
     * Calculate break hours (decimal)
     */
    public function getBreakHoursAttribute()
    {
        return $this->break_minutes ? round($this->break_minutes / 60, 2) : null;
    }

    /**
     * Check if the user is currently clocked in
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Calculate total work duration when clocking out
     */
    public function calculateWorkDuration()
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return;
        }

        $clockIn = Carbon::parse($this->clock_in_time);
        $clockOut = Carbon::parse($this->clock_out_time);
        
        $totalMinutes = $clockOut->diffInMinutes($clockIn);
        $breakMinutes = $this->break_minutes ?? 0;
        
        $this->total_work_minutes = $totalMinutes - $breakMinutes;
        
        // Calculate overtime based on attendance settings
        $this->calculateOvertime();
    }

    /**
     * Calculate overtime based on settings
     */
    protected function calculateOvertime()
    {
        $settings = AttendanceSetting::getSettings();
        if (!$settings || !$settings->enable_overtime) {
            return;
        }

        $workHours = $settings->getWorkHoursForDay(Carbon::parse($this->clock_in_time)->format('Y-m-d'));
        if (!$workHours) {
            // If it's a weekend, all hours count as overtime
            $this->overtime_minutes = $this->total_work_minutes;
            return;
        }

        // Calculate standard work minutes
        $startTime = Carbon::parse($this->clock_in_time->format('Y-m-d') . ' ' . $workHours['start_time']);
        $endTime = Carbon::parse($this->clock_in_time->format('Y-m-d') . ' ' . $workHours['end_time']);
        $standardWorkMinutes = $endTime->diffInMinutes($startTime);
        
        // Calculate overtime minutes
        $this->overtime_minutes = max(0, $this->total_work_minutes - $standardWorkMinutes);
    }

    /**
     * Check if attendance record is for today
     */
    public function isToday()
    {
        return $this->clock_in_time && Carbon::parse($this->clock_in_time)->isToday();
    }
}
