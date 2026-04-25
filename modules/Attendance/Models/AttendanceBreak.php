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
use Carbon\Carbon;
use Modules\Company\CompanyCore\Models\Company;

/**
 * @property string $id
 * @property string $attendance_id
 * @property string $company_id
 * @property Carbon|null $start_time
 * @property Carbon|null $end_time
 * @property int|null $duration_minutes
 * @property string|null $notes
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Attendance $attendance
 * @property-read Company $company
 */
class AttendanceBreak extends Model
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use CustomBelongsToTenant;

    protected $table = 'attendance_breaks';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'attendance_id',
        'company_id',
        'start_time',
        'end_time',
        'duration_minutes',
        'notes',
        'source',
    ];

    protected $casts = [
        'id' => 'string',
        'attendance_id' => 'string',
        'company_id' => 'string',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    protected $dates = [
        'start_time',
        'end_time',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Get the attendance record that owns this break.
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the company that owns this break.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if the break is currently active (started but not ended).
     */
    public function isActive(): bool
    {
        return !is_null($this->start_time) && is_null($this->end_time);
    }

    /**
     * Check if the break is completed.
     */
    public function isCompleted(): bool
    {
        return !is_null($this->start_time) && !is_null($this->end_time);
    }

    /**
     * Calculate duration in minutes and update the model.
     */
    public function calculateDuration(): int
    {
        if (!$this->start_time || !$this->end_time) {
            $this->duration_minutes = 0;
            $this->save();
            return 0;
        }

        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        $durationMinutes = (int) $endTime->diffInMinutes($startTime);
        
        $this->duration_minutes = $durationMinutes;
        $this->save();
        return $durationMinutes;
    }

    /**
     * Get formatted duration.
     */
    public function getFormattedDuration(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return '0m';
        }

        $minutes = $this->duration_minutes ?? Carbon::parse($this->end_time)->diffInMinutes(Carbon::parse($this->start_time));
        
        if ($minutes >= 60) {
            $hours = intval($minutes / 60);
            $remainingMinutes = $minutes % 60;
            return "{$hours}h {$remainingMinutes}m";
        }
        
        return "{$minutes}m";
    }
}
