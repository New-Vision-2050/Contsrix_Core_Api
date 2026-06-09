<?php

declare(strict_types=1);

namespace Modules\Attendance\Models;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;
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
use Modules\Company\ManagementHierarchy\Models\ManagementHierarchy;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * @property string $id
 * @property string $company_id
 * @property array|null $user_ids
 * @property array|null $department_ids
 * @property array|null $branch_ids
 * @property array|null $branch_locations
 * @property string $constraint_type
 * @property string $constraint_name
 * @property array $constraint_config
 * @property boolean $is_active
 * @property boolean $inherit_from_parent
 * @property integer $priority
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $notes
 * @property integer|null $out_zone_minutes
 * @property array|null $out_zone_rules
 * @property integer $max_working_hours
 * @property-read Company $company
 * @property-read User $creator
 * @property-read User $updater
 */
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
        // 'user_ids',
        'department_ids',
        'branch_ids',
        'branch_locations',
        'constraint_type',
        'constraint_name',
        'constraint_config',
        'max_over_time',
        'out_zone_minutes',
        'out_zone_rules',
        'max_working_hours',
        'is_active',
        'inherit_from_parent',
        'priority',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
        'notes',
        'country_id',
        'time_zone_id',
    ];

    protected $casts = [
        'id' => 'string',
        'company_id' => 'string',
        // 'user_ids' => 'array',
        'department_ids' => 'array',
        'branch_ids' => 'array',
        'branch_locations' => 'array',
        'created_by' => 'string',
        'updated_by' => 'string',
        'constraint_config' => 'array',
        'max_over_time' => 'integer',
        'out_zone_minutes' => 'integer',
        'out_zone_rules' => 'array',
        'max_working_hours' => 'integer',
        'is_active' => 'boolean',
        'inherit_from_parent' => 'boolean',
        'priority' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'country_id' => 'integer',
        'time_zone_id' => 'integer',
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    const REGULAR = 'regular';

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
    const LOCATION_RADIUS_ENFORCEMENT = 'radius_enforcement';

    // Constraint name constants for time
    const TIME_SHIFT_ENFORCEMENT = 'shift_enforcement';
    const TIME_EARLY_PREVENTION = 'early_prevention';
    const TIME_LATE_RESTRICTION = 'late_restriction';
    const TIME_BREAK_LIMITS = 'break_limits';
    const TIME_OVERTIME_APPROVAL = 'overtime_approval';
    const TIME_MULTIPLE_PERIODS = 'multiple_periods';
    public const TIME_LATE_CLOCK_OUT = 'late_clock_out';
    public const TIME_BREAK_TIME_LIMITS = 'break_time_limits';

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
    const COMPLIANCE_OFFICE_VERIFICATION = 'office_verification';

    /**
     * Get the company that owns the constraint.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    // public function user(): BelongsTo
    // {
    //     return $this->belongsTo(User::class, 'user_id');
    // }

    /**
     * Get the users that this constraint applies to.
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'attendance_constraint_user',
            'attendance_constraint_id',
            'user_id'
        );
    }

    protected function departments()
    {
        return $this->hasMany(ManagementHierarchy::class, 'id', 'department_ids');
    }
    /**
     * Get the branches that this constraint applies to (if branch-specific).
     */
    // public function branches()
    // {
    //     if (empty($this->branch_ids)) {
    //         return collect();
    //     }

    //     return ManagementHierarchy::whereIn('id', $this->branch_ids)->get();
    // }

    /**
     * Check if constraint applies to a specific branch.
     */
    public function appliesToBranch(string $branchId): bool
    {
        // Company-wide constraints apply to all branches
        if (empty($this->branch_ids)) {
            return true;
        }

        // Check if branch is in the list
        return in_array($branchId, $this->branch_ids);
    }

    /**
     * Add a branch to this constraint.
     */
    public function addBranch(string $branchId): void
    {
        $branchIds = $this->branch_ids ?? [];

        if (!in_array($branchId, $branchIds)) {
            $branchIds[] = $branchId;
            $this->branch_ids = $branchIds;
            $this->save();
        }
    }

    /**
     * Remove a branch from this constraint.
     */
    public function removeBranch(string $branchId): void
    {
        $branchIds = $this->branch_ids ?? [];

        $branchIds = array_filter($branchIds, fn($id) => $id !== $branchId);
        $this->branch_ids = array_values($branchIds);

        // Also remove the location for this branch
        $this->removeBranchLocation($branchId);

        $this->save();
    }

    /**
     * Set location for a specific branch.
     */
    public function setBranchLocation(string $branchId, array $location): void
    {
        $branchLocations = $this->branch_locations ?? [];
        $branchLocations[$branchId] = $location;
        $this->branch_locations = $branchLocations;
        $this->save();
    }

    /**
     * Get location for a specific branch.
     */
    public function getBranchLocation(string $branchId): ?array
    {
        $branchLocations = $this->branch_locations ?? [];
        return $branchLocations[$branchId] ?? null;
    }

    /**
     * Remove location for a specific branch.
     */
    public function removeBranchLocation(string $branchId): void
    {
        $branchLocations = $this->branch_locations ?? [];
        unset($branchLocations[$branchId]);
        $this->branch_locations = $branchLocations;
    }

    /**
     * Get all branch locations.
     */
    public function getAllBranchLocations(): array
    {
        return $this->branch_locations ?? [];
    }

    /**
     * Set multiple branch locations at once.
     */
    public function setBranchLocations(array $branchLocations): void
    {
        $this->branch_locations = $branchLocations;
        $this->save();
    }

    /**
     * Check if a branch has a custom location set.
     */
    public function hasBranchLocation(string $branchId): bool
    {
        $branchLocations = $this->branch_locations ?? [];
        return isset($branchLocations[$branchId]);
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
     * Scope to get constraints for a specific branch.
     */
    public function scopeForBranch($query, string $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->whereJsonContains('branch_ids', $branchId)
              ->orWhereNull('branch_ids'); // Include company-wide constraints
        });
    }

    /**
     * Scope to get constraints applicable to a branch (including inherited).
     */
    public function scopeApplicableToBranch($query, string $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->whereJsonContains('branch_ids', $branchId)
              ->orWhere(function ($subQ) use ($branchId) {
                  // Include constraints from parent branches if inheritance is enabled
                  $subQ->where('inherit_from_parent', true)
                       ->whereJsonContains('branch_ids', $branchId);
              })
              ->orWhereNull('branch_ids'); // Include company-wide constraints
        });
    }

    /**
     * Scope to get only branch-specific constraints (excluding company-wide).
     */
    public function scopeBranchSpecific($query)
    {
        return $query->whereNotNull('branch_ids');
    }

    /**
     * Scope to get only company-wide constraints.
     */
    public function scopeCompanyWide($query)
    {
        return $query->whereNull('branch_ids');
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
        $checkDate = $date ? Carbon::parse($date) : now();

        $startValid = !$this->start_date || $checkDate->gte($this->start_date);
        $endValid = !$this->end_date || $checkDate->lte($this->end_date);

        return $this->is_active && $startValid && $endValid;
    }

    /**
     * Get all constraint types.
     */
    // public static function getConstraintTypes(): array
    // {
    //     return [
    //         self::TYPE_LOCATION => 'Location-based Constraints',
    //         self::TYPE_TIME => 'Time-based Constraints',
    //         self::TYPE_DEVICE => 'Device-based Constraints',
    //         self::TYPE_ROLE => 'Role-based Constraints',
    //         self::TYPE_BEHAVIORAL => 'Behavioral Constraints',
    //         self::TYPE_SECURITY => 'Security Constraints',
    //         self::TYPE_COMPLIANCE => 'Compliance Constraints',
    //     ];
    // }

        /**
     * Get all constraint types.
     */
    public static function getConstraintTypes(): array
    {
        return collect([
            self::REGULAR => __('validation.regular'),
        ])->map(function ($name, $code) {
            return [
                'name' => $name,
                'code' => $code,
            ];
        })->values()->toArray();
    }
    public static function getConstraintArrayTypes(): array
    {
        return [
            self::REGULAR => __('validation.regular'),

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
                self::TIME_MULTIPLE_PERIODS => 'Multiple Periods per Day',
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

    public function branches()
    {
        return $this->hasMany(ManagementHierarchy::class, 'id', 'branch_ids');
    }

    public function additionalLocations(): HasMany
    {
        return $this->hasMany(AttendanceConstraintLocation::class, 'attendance_constraint_id');
    }
    public function managementHierarchies()
    {
        return $this->morphedByMany(
            ManagementHierarchy::class,
            'constrainable',
            'constrainables',  // table name
            'attendance_constraint_id',
            'constrainable_id'
        );
    }
}
