<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;
use Carbon\Carbon;

class BreakRecord extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'break_records';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'attendance_record_id',
        'break_start_time',
        'break_end_time',
        'break_type',
        'duration_minutes',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'break_start_time' => 'datetime',
        'break_end_time' => 'datetime',
    ];

    /**
     * Get the attendance record that owns this break.
     */
    public function attendanceRecord()
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    /**
     * Check if break is active (not yet ended)
     * 
     * @return bool
     */
    public function isActive()
    {
        return !is_null($this->break_start_time) && is_null($this->break_end_time);
    }

    /**
     * Calculate and update break duration in minutes
     */
    public function calculateDuration()
    {
        if (!$this->break_start_time || !$this->break_end_time) {
            return;
        }

        $start = Carbon::parse($this->break_start_time);
        $end = Carbon::parse($this->break_end_time);
        
        $this->duration_minutes = $end->diffInMinutes($start);
    }

    /**
     * Format duration as hours and minutes
     * 
     * @return string
     */
    public function getFormattedDurationAttribute()
    {
        if (!$this->duration_minutes) {
            return '0 minutes';
        }
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        $result = '';
        if ($hours > 0) {
            $result .= $hours . ' ' . ($hours == 1 ? 'hour' : 'hours');
        }
        
        if ($minutes > 0) {
            if ($result) {
                $result .= ' and ';
            }
            $result .= $minutes . ' ' . ($minutes == 1 ? 'minute' : 'minutes');
        }
        
        return $result;
    }

    /**
     * End break and calculate duration
     */
    public function endBreak()
    {
        $this->break_end_time = now();
        $this->calculateDuration();
        $this->save();
        
        // Update parent attendance record with break minutes
        if ($this->attendanceRecord) {
            $totalBreakMinutes = $this->attendanceRecord->breakRecords->sum('duration_minutes');
            $this->attendanceRecord->update([
                'break_minutes' => $totalBreakMinutes
            ]);
        }
    }
}
