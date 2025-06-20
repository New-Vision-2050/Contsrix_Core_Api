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

class AttendanceConstraintViolation extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'attendance_constraint_violations';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'attendance_id',
        'constraint_id',
        'violation_type',
        'violation_details',
        'severity_level',
        'status',
        'resolved_by',
        'resolved_at',
        'resolution_notes',
        'auto_resolved',
        'notification_sent',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'attendance_id' => UuidCast::class,
        'constraint_id' => UuidCast::class,
        'resolved_by' => UuidCast::class,
        'violation_details' => 'array',
        'auto_resolved' => 'boolean',
        'notification_sent' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    protected $dates = [
        'resolved_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Violation type constants
    const TYPE_LOCATION_VIOLATION = 'location_violation';
    const TYPE_TIME_VIOLATION = 'time_violation';
    const TYPE_DEVICE_VIOLATION = 'device_violation';
    const TYPE_ROLE_VIOLATION = 'role_violation';
    const TYPE_BEHAVIORAL_VIOLATION = 'behavioral_violation';
    const TYPE_SECURITY_VIOLATION = 'security_violation';
    const TYPE_COMPLIANCE_VIOLATION = 'compliance_violation';

    // Severity level constants
    const SEVERITY_LOW = 'low';
    const SEVERITY_MEDIUM = 'medium';
    const SEVERITY_HIGH = 'high';
    const SEVERITY_CRITICAL = 'critical';

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACKNOWLEDGED = 'acknowledged';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_DISMISSED = 'dismissed';

    /**
     * Get the company that owns the violation.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the user that violated the constraint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the attendance record associated with this violation.
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class, 'attendance_id');
    }

    /**
     * Get the constraint that was violated.
     */
    public function constraint(): BelongsTo
    {
        return $this->belongsTo(AttendanceConstraint::class, 'constraint_id');
    }

    /**
     * Get the user who resolved this violation.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Scope to get pending violations.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get violations by severity.
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity_level', $severity);
    }

    /**
     * Scope to get violations by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }

    /**
     * Scope to get unresolved violations.
     */
    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_ACKNOWLEDGED]);
    }

    /**
     * Scope to get critical violations.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity_level', self::SEVERITY_CRITICAL);
    }

    /**
     * Mark violation as resolved.
     */
    public function resolve(string $userId, string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_RESOLVED,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Mark violation as dismissed.
     */
    public function dismiss(string $userId, string $notes = null): bool
    {
        return $this->update([
            'status' => self::STATUS_DISMISSED,
            'resolved_by' => $userId,
            'resolved_at' => now(),
            'resolution_notes' => $notes,
        ]);
    }

    /**
     * Check if violation is resolved.
     */
    public function isResolved(): bool
    {
        return in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_DISMISSED]);
    }

    /**
     * Get all violation types.
     */
    public static function getViolationTypes(): array
    {
        return [
            self::TYPE_LOCATION_VIOLATION => 'Location Violation',
            self::TYPE_TIME_VIOLATION => 'Time Violation',
            self::TYPE_DEVICE_VIOLATION => 'Device Violation',
            self::TYPE_ROLE_VIOLATION => 'Role Violation',
            self::TYPE_BEHAVIORAL_VIOLATION => 'Behavioral Violation',
            self::TYPE_SECURITY_VIOLATION => 'Security Violation',
            self::TYPE_COMPLIANCE_VIOLATION => 'Compliance Violation',
        ];
    }

    /**
     * Get all severity levels.
     */
    public static function getSeverityLevels(): array
    {
        return [
            self::SEVERITY_LOW => 'Low',
            self::SEVERITY_MEDIUM => 'Medium',
            self::SEVERITY_HIGH => 'High',
            self::SEVERITY_CRITICAL => 'Critical',
        ];
    }

    /**
     * Get all statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_ACKNOWLEDGED => 'Acknowledged',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_DISMISSED => 'Dismissed',
        ];
    }
}
