<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;

use App\Casts\UuidCast;
use App\Traits\CustomBelongsToTenant;
use BasePackage\Shared\Traits\BaseFilterable;
use BasePackage\Shared\Traits\UuidTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;
use OwenIt\Auditing\Contracts\Auditable;

class AttendanceTask extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attendance_tasks';

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'constraint_id',
        'attendance_id',
        'type',
        'details',
        'status',
        'priority',
        'assigned_to',
        'assigned_at',
        'completed_at',
        'due_date',
        'completion_notes',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'constraint_id' => UuidCast::class,
        'attendance_id' => UuidCast::class,
        'details' => 'array',
        'assigned_to' => UuidCast::class,
        'created_by' => UuidCast::class,
        'updated_by' => UuidCast::class,
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'assigned_at',
        'completed_at',
        'due_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Task status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_REJECTED = 'rejected';
    
    // Task type constants
    const TYPE_CONSTRAINT_EXCEPTION = 'constraint_exception';
    const TYPE_LOCATION_EXCEPTION = 'location_exception';
    const TYPE_VIOLATION_HANDLING = 'violation_handling';
    const TYPE_TEMPORARY_LOCATION = 'temporary_location';

    /**
     * Get the user that the task is about.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the constraint related to this task.
     */
    public function constraint(): BelongsTo
    {
        return $this->belongsTo(AttendanceConstraint::class, 'constraint_id');
    }

    /**
     * Get the attendance record related to this task.
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    /**
     * Get the user assigned to handle this task.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Mark task as completed
     *
     * @param string $completedBy User ID of who completed the task
     * @param string|null $notes Completion notes
     * @return bool
     */
    public function markAsCompleted(string $completedBy, ?string $notes = null): bool
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = Carbon::now();
        $this->updated_by = $completedBy;
        
        if ($notes) {
            $this->completion_notes = $notes;
        }
        
        return $this->save();
    }

    /**
     * Mark task as rejected
     *
     * @param string $rejectedBy User ID of who rejected the task
     * @param string $reason Rejection reason
     * @return bool
     */
    public function markAsRejected(string $rejectedBy, string $reason): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->completed_at = Carbon::now();
        $this->updated_by = $rejectedBy;
        $this->completion_notes = $reason;
        
        return $this->save();
    }

    /**
     * Check if task is overdue
     *
     * @return bool
     */
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }
        
        return $this->status !== self::STATUS_COMPLETED 
            && $this->status !== self::STATUS_REJECTED 
            && Carbon::now()->gt($this->due_date);
    }
    
    /**
     * Get tasks that are due soon (within the next 24 hours)
     *
     * @param int $hoursThreshold Hours threshold for "due soon"
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function dueSoonTasks(int $hoursThreshold = 24)
    {
        return static::whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->where('due_date', '<=', Carbon::now()->addHours($hoursThreshold))
            ->where('due_date', '>', Carbon::now())
            ->orderBy('due_date')
            ->get();
    }
    
    /**
     * Get tasks assigned to a specific user
     *
     * @param string $userId User ID
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function assignedToUser(string $userId)
    {
        return static::where('assigned_to', $userId)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->orderBy('due_date')
            ->get();
    }
    
    /**
     * Get overdue tasks
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function overdueTasks()
    {
        return static::whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])
            ->where('due_date', '<', Carbon::now())
            ->orderBy('due_date')
            ->get();
    }
}
