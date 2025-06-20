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

class AttendanceConstraint extends Model implements Auditable
{
    use UuidTrait;
    use BaseFilterable;
    use SoftDeletes;
    use \OwenIt\Auditing\Auditable;
    use CustomBelongsToTenant;

    protected $table = 'attendance_constraints';

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'constraint_type',
        'constraint_name',
        'constraint_config',
        'is_active',
        'priority',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'id' => UuidCast::class,
        'company_id' => UuidCast::class,
        'user_id' => UuidCast::class,
        'department_id' => UuidCast::class,
        'created_by' => UuidCast::class,
        'updated_by' => UuidCast::class,
        'constraint_config' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    // Constraint type constants
    const TYPE_LOCATION = 'location';
    const TYPE_TIME = 'time';
    const TYPE_DEVICE = 'device';
    const TYPE_ROLE = 'role';
    const TYPE_BEHAVIORAL = 'behavioral';
    const TYPE_SECURITY = 'security';
    const TYPE_COMPLIANCE = 'compliance';

    // Constraint name constants for location
    const LOCATION_GEOFENCING = 'geofencing';
    const LOCATION_IP_RESTRICTION = 'ip_restriction';
    const LOCATION_OFFICE_VERIFICATION = 'office_verification';
    const LOCATION_REMOTE_ZONES = 'remote_zones';
    const LOCATION_MULTI_LOCATION = 'multi_location';

    // Constraint name constants for time
    const TIME_SHIFT_ENFORCEMENT = 'shift_enforcement';
    const TIME_EARLY_PREVENTION = 'early_prevention';
    const TIME_LATE_RESTRICTION = 'late_restriction';
    const TIME_BREAK_LIMITS = 'break_limits';
    const TIME_OVERTIME_APPROVAL = 'overtime_approval';

    // Constraint name constants for device
    const DEVICE_AUTHORIZED_ONLY = 'authorized_only';
    const DEVICE_FINGERPRINTING = 'fingerprinting';
    const DEVICE_SINGLE_POLICY = 'single_policy';
    const DEVICE_APP_RESTRICTIONS = 'app_restrictions';
    const DEVICE_BROWSER_RESTRICTIONS = 'browser_restrictions';

    // Constraint name constants for role
    const ROLE_DEPARTMENT_RULES = 'department_rules';
    const ROLE_LEVEL_RESTRICTIONS = 'level_restrictions';
    const ROLE_PROBATIONARY_RULES = 'probationary_rules';
    const ROLE_CONTRACT_CONSTRAINTS = 'contract_constraints';
    const ROLE_SUPERVISOR_OVERRIDE = 'supervisor_override';

    // Constraint name constants for behavioral
    const BEHAVIORAL_CONSECUTIVE_LIMIT = 'consecutive_limit';
    const BEHAVIORAL_WEEKLY_HOURS = 'weekly_hours';
    const BEHAVIORAL_REST_PERIODS = 'rest_periods';
    const BEHAVIORAL_HOLIDAY_WORK = 'holiday_work';
    const BEHAVIORAL_PATTERN_MONITORING = 'pattern_monitoring';

    // Constraint name constants for security
    const SECURITY_TWO_FACTOR = 'two_factor';
    const SECURITY_BIOMETRIC = 'biometric';
    const SECURITY_AUDIT_TRAIL = 'audit_trail';
    const SECURITY_FRAUD_DETECTION = 'fraud_detection';
    const SECURITY_DATA_ENCRYPTION = 'data_encryption';

    // Constraint name constants for compliance
    const COMPLIANCE_LABOR_LAW = 'labor_law';
    const COMPLIANCE_UNION_AGREEMENT = 'union_agreement';
    const COMPLIANCE_INDUSTRY_RULES = 'industry_rules';
    const COMPLIANCE_GOVERNMENT_REPORTING = 'government_reporting';
    const COMPLIANCE_DOCUMENTATION = 'documentation';

    /**
     * Get the company that owns the constraint.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Get the user that this constraint applies to (if user-specific).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who created this constraint.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this constraint.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope to get active constraints.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get constraints by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('constraint_type', $type);
    }

    /**
     * Scope to get constraints by name.
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('constraint_name', $name);
    }

    /**
     * Scope to get constraints for a specific user.
     */
    public function scopeForUser($query, string $userId)
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhereNull('user_id'); // Include company-wide constraints
        });
    }

    /**
     * Scope to get constraints ordered by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * Check if constraint is currently valid based on date range.
     */
    public function isValidForDate($date = null): bool
    {
        $checkDate = $date ? carbon($date) : now();
        
        $startValid = !$this->start_date || $checkDate->gte($this->start_date);
        $endValid = !$this->end_date || $checkDate->lte($this->end_date);
        
        return $this->is_active && $startValid && $endValid;
    }

    /**
     * Get all constraint types.
     */
    public static function getConstraintTypes(): array
    {
        return [
            self::TYPE_LOCATION => 'Location-based Constraints',
            self::TYPE_TIME => 'Time-based Constraints',
            self::TYPE_DEVICE => 'Device-based Constraints',
            self::TYPE_ROLE => 'Role-based Constraints',
            self::TYPE_BEHAVIORAL => 'Behavioral Constraints',
            self::TYPE_SECURITY => 'Security Constraints',
            self::TYPE_COMPLIANCE => 'Compliance Constraints',
        ];
    }

    /**
     * Get constraint names by type.
     */
    public static function getConstraintNamesByType(string $type): array
    {
        $constraints = [
            self::TYPE_LOCATION => [
                self::LOCATION_GEOFENCING => 'Geofencing',
                self::LOCATION_IP_RESTRICTION => 'IP Address Restrictions',
                self::LOCATION_OFFICE_VERIFICATION => 'Office Location Verification',
                self::LOCATION_REMOTE_ZONES => 'Remote Work Zones',
                self::LOCATION_MULTI_LOCATION => 'Multi-location Support',
            ],
            self::TYPE_TIME => [
                self::TIME_SHIFT_ENFORCEMENT => 'Shift Schedule Enforcement',
                self::TIME_EARLY_PREVENTION => 'Early Clock-in Prevention',
                self::TIME_LATE_RESTRICTION => 'Late Clock-out Restrictions',
                self::TIME_BREAK_LIMITS => 'Break Time Limits',
                self::TIME_OVERTIME_APPROVAL => 'Overtime Approval Requirements',
            ],
            self::TYPE_DEVICE => [
                self::DEVICE_AUTHORIZED_ONLY => 'Authorized Device Registration',
                self::DEVICE_FINGERPRINTING => 'Device Fingerprinting',
                self::DEVICE_SINGLE_POLICY => 'Single Device Policy',
                self::DEVICE_APP_RESTRICTIONS => 'Mobile App Restrictions',
                self::DEVICE_BROWSER_RESTRICTIONS => 'Browser Restrictions',
            ],
            self::TYPE_ROLE => [
                self::ROLE_DEPARTMENT_RULES => 'Department-specific Rules',
                self::ROLE_LEVEL_RESTRICTIONS => 'Employee Level Restrictions',
                self::ROLE_PROBATIONARY_RULES => 'Probationary Employee Rules',
                self::ROLE_CONTRACT_CONSTRAINTS => 'Contract Type Constraints',
                self::ROLE_SUPERVISOR_OVERRIDE => 'Supervisor Override Permissions',
            ],
            self::TYPE_BEHAVIORAL => [
                self::BEHAVIORAL_CONSECUTIVE_LIMIT => 'Consecutive Days Limit',
                self::BEHAVIORAL_WEEKLY_HOURS => 'Weekly Hour Limits',
                self::BEHAVIORAL_REST_PERIODS => 'Mandatory Rest Periods',
                self::BEHAVIORAL_HOLIDAY_WORK => 'Holiday Work Restrictions',
                self::BEHAVIORAL_PATTERN_MONITORING => 'Attendance Pattern Monitoring',
            ],
            self::TYPE_SECURITY => [
                self::SECURITY_TWO_FACTOR => 'Two-Factor Authentication',
                self::SECURITY_BIOMETRIC => 'Biometric Verification',
                self::SECURITY_AUDIT_TRAIL => 'Audit Trail Requirements',
                self::SECURITY_FRAUD_DETECTION => 'Fraud Detection',
                self::SECURITY_DATA_ENCRYPTION => 'Data Encryption',
            ],
            self::TYPE_COMPLIANCE => [
                self::COMPLIANCE_LABOR_LAW => 'Labor Law Compliance',
                self::COMPLIANCE_UNION_AGREEMENT => 'Union Agreement Adherence',
                self::COMPLIANCE_INDUSTRY_RULES => 'Industry-specific Rules',
                self::COMPLIANCE_GOVERNMENT_REPORTING => 'Government Reporting',
                self::COMPLIANCE_DOCUMENTATION => 'Documentation Requirements',
            ],
        ];

        return $constraints[$type] ?? [];
    }
}
