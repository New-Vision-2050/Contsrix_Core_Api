<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\CustomBelongsToTenant;
use OwenIt\Auditing\Contracts\Auditable;

class AttendanceSetting extends Model implements Auditable
{
    use CustomBelongsToTenant;
    use \OwenIt\Auditing\Auditable;

    protected $table = 'attendance_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'work_start_time',
        'work_end_time',
        'grace_period_minutes',
        'working_days_per_week',
        'weekend_days',
        'enable_overtime',
        'overtime_rate',
        'overtime_start_after_minutes',
        'break_time_minutes',
        'max_breaks_per_day',
        'default_annual_leave_days',
        'default_sick_leave_days',
        'allow_leave_carryover',
        'max_leave_carryover_days',
        'enforce_location_based_attendance',
        'location_accuracy_threshold',
        'allowed_attendance_locations',
        'notify_manager_on_absence',
        'notify_employee_on_overtime',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'weekend_days' => 'array',
        'allowed_attendance_locations' => 'array',
        'enable_overtime' => 'boolean',
        'allow_leave_carryover' => 'boolean',
        'enforce_location_based_attendance' => 'boolean',
        'notify_manager_on_absence' => 'boolean',
        'notify_employee_on_overtime' => 'boolean',
    ];

    /**
     * Get settings for specific tenant
     *
     * @return AttendanceSetting|null
     */
    public static function getSettings()
    {
        return self::first();
    }

    /**
     * Check if the current day is a weekend
     *
     * @param string|null $date Date to check in Y-m-d format
     * @return bool
     */
    public function isWeekend(?string $date = null): bool
    {
        $date = $date ? new \DateTime($date) : new \DateTime();
        $dayOfWeek = (int) $date->format('w'); // 0 (Sun) to 6 (Sat)
        
        // Check if current day is in weekend_days array
        return in_array($dayOfWeek, $this->weekend_days ?? [0, 6]);
    }

    /**
     * Get the work hours for a specific day
     * 
     * @param string|null $date Date to check in Y-m-d format
     * @return array|null Returns null if it's a weekend, otherwise returns start and end times
     */
    public function getWorkHoursForDay(?string $date = null): ?array
    {
        if ($this->isWeekend($date)) {
            return null;
        }

        return [
            'start_time' => $this->work_start_time,
            'end_time' => $this->work_end_time,
        ];
    }
}
