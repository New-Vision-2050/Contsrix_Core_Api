# Multi-Branch Attendance Constraints Guide

## Overview

The Attendance module supports applying constraints to multiple branches through several mechanisms:

1. **Company-wide Constraints**: Apply to all branches (branch_id = null)
2. **Branch-specific Constraints**: Apply to specific branches
3. **Inherited Constraints**: Child branches inherit from parent branches
4. **Bulk Operations**: Apply constraints to multiple branches at once

## 🏢 Branch Hierarchy Support

### Branch Relationship Model
```php
// AttendanceConstraint model supports branch relationships
class AttendanceConstraint extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',           // Specific branch or null for company-wide
        'inherit_from_parent', // Enable inheritance from parent branch
        'constraint_type',
        'constraint_config',
        'is_active'
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(ManagementHierarchy::class, 'branch_id');
    }
}
```

### Query Scopes for Multi-Branch Support
```php
// Get constraints for a specific branch (including company-wide)
$constraints = AttendanceConstraint::forBranch($branchId)->get();

// Get all applicable constraints (including inherited)
$constraints = AttendanceConstraint::applicableToBranch($branchId)->get();

// Get only branch-specific constraints
$constraints = AttendanceConstraint::branchSpecific()->get();

// Get only company-wide constraints
$constraints = AttendanceConstraint::companyWide()->get();
```

## 🚀 Methods to Apply Constraints to Multiple Branches

### 1. Company-wide Constraints (All Branches)

#### Create Company-wide Constraint
```php
// This constraint applies to ALL branches in the company
$constraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'branch_id' => null,  // NULL means company-wide
    'name' => 'Company-wide Office Hours',
    'type' => 'time_multiple_periods',
    'config' => [
        'weekly_schedule' => [
            'monday' => [
                'enabled' => true,
                'periods' => [
                    [
                        'name' => 'Standard Hours',
                        'start_time' => '09:00',
                        'end_time' => '17:00',
                        'spans_next_day' => false,
                        'grace_period_before' => 15,
                        'grace_period_after' => 15
                    ]
                ]
            ]
            // ... other days
        ]
    ],
    'is_active' => true
]);
```

#### API Request for Company-wide Constraint
```http
POST /api/v1/attendance/constraints
Content-Type: application/json
Authorization: Bearer {jwt_token}

{
  "name": "Company-wide Office Hours",
  "type": "time_multiple_periods",
  "branch_id": null,
  "config": {
    "weekly_schedule": {
      "monday": {
        "enabled": true,
        "periods": [
          {
            "name": "Standard Hours",
            "start_time": "09:00",
            "end_time": "17:00",
            "spans_next_day": false,
            "grace_period_before": 15,
            "grace_period_after": 15
          }
        ]
      }
    }
  },
  "is_active": true
}
```

### 2. Branch Inheritance (Parent to Child)

#### Create Parent Constraint with Inheritance
```php
// Create constraint on parent branch that children can inherit
$parentConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'branch_id' => $parentBranchId,
    'name' => 'Regional Office Hours',
    'type' => 'time_multiple_periods',
    'config' => $multiplePeriodsConfig,
    'inherit_from_parent' => true,  // Enable inheritance
    'is_active' => true
]);

// Child branches automatically inherit this constraint
```

#### API Request with Inheritance
```http
POST /api/v1/attendance/constraints
Content-Type: application/json

{
  "name": "Regional Office Hours",
  "type": "time_multiple_periods",
  "branch_id": "parent-branch-uuid",
  "inherit_from_parent": true,
  "config": {
    "weekly_schedule": { /* schedule config */ }
  },
  "is_active": true
}
```

### 3. Bulk Operations (Multiple Specific Branches)

#### Bulk Assign Constraint to Multiple Branches
```php
// Create a constraint first
$constraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'Multi-Branch Constraint',
    'type' => 'time_multiple_periods',
    'config' => $config,
    'is_active' => true
]);

// Then bulk assign to multiple branches
$branchIds = ['branch-1-uuid', 'branch-2-uuid', 'branch-3-uuid'];

foreach ($branchIds as $branchId) {
    $constraint->replicate()->fill([
        'branch_id' => $branchId
    ])->save();
}
```

#### API Bulk Assignment
```http
POST /api/v1/attendance/constraints/{constraint-id}/bulk-assign
Content-Type: application/json

{
  "branch_ids": [
    "branch-1-uuid",
    "branch-2-uuid", 
    "branch-3-uuid"
  ]
}
```

#### Bulk Assign Existing Constraints to Branch
```http
POST /api/v1/attendance/constraints/bulk-assign-to-branch/{branch-id}
Content-Type: application/json

{
  "constraint_ids": [
    "constraint-1-uuid",
    "constraint-2-uuid",
    "constraint-3-uuid"
  ]
}
```

### 4. Programmatic Multi-Branch Application

#### Service Method for Multi-Branch Constraints
```php
use Modules\Attendance\Services\AttendanceConstraintService;

class MultiBranchConstraintService
{
    public function applyConstraintToMultipleBranches(
        array $branchIds,
        array $constraintData
    ): array {
        $results = [];
        
        foreach ($branchIds as $branchId) {
            $constraint = AttendanceConstraint::create([
                'company_id' => $constraintData['company_id'],
                'branch_id' => $branchId,
                'name' => $constraintData['name'] . " - Branch {$branchId}",
                'type' => $constraintData['type'],
                'config' => $constraintData['config'],
                'is_active' => true
            ]);
            
            $results[] = $constraint;
        }
        
        return $results;
    }
    
    public function applyToAllBranches(string $companyId, array $constraintData): array
    {
        // Get all branches for the company
        $branches = ManagementHierarchy::where('company_id', $companyId)
            ->where('type', 'branch')
            ->pluck('id')
            ->toArray();
            
        return $this->applyConstraintToMultipleBranches($branches, $constraintData);
    }
}
```

## 📋 Complete Multi-Branch Examples

### Example 1: Restaurant Chain with Different Schedules

```php
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;

// Main office (company-wide base schedule)
$baseConfig = MultiplePeriodsConfig::standardOfficeHours();
AttendanceConstraint::create([
    'company_id' => $companyId,
    'branch_id' => null, // Company-wide
    'name' => 'Base Office Hours',
    'type' => 'time_multiple_periods',
    'config' => $baseConfig->toArray(),
    'is_active' => true
]);

// Restaurant branches with service hours
$restaurantConfig = MultiplePeriodsConfig::restaurantServiceHours();
$restaurantBranches = ['restaurant-1', 'restaurant-2', 'restaurant-3'];

foreach ($restaurantBranches as $branchId) {
    AttendanceConstraint::create([
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'name' => 'Restaurant Service Hours',
        'type' => 'time_multiple_periods',
        'config' => $restaurantConfig->toArray(),
        'is_active' => true
    ]);
}

// Security branches with 24/7 shifts
$securityConfig = MultiplePeriodsConfig::securityShifts();
$securityBranches = ['security-1', 'security-2'];

foreach ($securityBranches as $branchId) {
    AttendanceConstraint::create([
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'name' => '24/7 Security Shifts',
        'type' => 'time_multiple_periods',
        'config' => $securityConfig->toArray(),
        'is_active' => true
    ]);
}
```

### Example 2: Regional Hierarchy with Inheritance

```php
// Regional constraint (parent level)
$regionalConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'branch_id' => $regionalOfficeId,
    'name' => 'Regional Business Hours',
    'type' => 'time_multiple_periods',
    'config' => MultiplePeriodsConfig::flexibleOfficeHours()->toArray(),
    'inherit_from_parent' => true,
    'is_active' => true
]);

// Child branches automatically inherit the regional constraint
// But can also have their own specific constraints
$localConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'branch_id' => $localBranchId,
    'name' => 'Local Lunch Break Policy',
    'type' => 'time',
    'config' => [
        'lunch_break' => [
            'start_time' => '12:00',
            'end_time' => '13:00',
            'mandatory' => true
        ]
    ],
    'is_active' => true
]);
```

## 🔧 API Endpoints for Multi-Branch Operations

### Get Constraints for Multiple Branches
```http
GET /api/v1/attendance/constraints?branch_ids[]=branch-1&branch_ids[]=branch-2&branch_ids[]=branch-3
```

### Bulk Operations
```http
# Bulk activate constraints across branches
POST /api/v1/attendance/constraints/bulk-activate
{
  "constraint_ids": ["id1", "id2", "id3"]
}

# Bulk assign to branch
POST /api/v1/attendance/constraints/bulk-assign-to-branch/{branch-id}
{
  "constraint_ids": ["id1", "id2", "id3"]
}

# Get branch hierarchy
GET /api/v1/management-hierarchy/branches
```

## 🎯 Best Practices for Multi-Branch Constraints

### 1. Hierarchy Design
```
Company-wide (branch_id = null)
├── Regional Constraints (inherit_from_parent = true)
│   ├── Local Branch Constraints
│   └── Specific Department Constraints
└── Special Branch Constraints (override regional)
```

### 2. Priority System
```php
// Use priority field to handle conflicts
AttendanceConstraint::create([
    'priority' => 1, // Higher priority = more specific
    'branch_id' => $specificBranchId,
    // ... other fields
]);

AttendanceConstraint::create([
    'priority' => 10, // Lower priority = more general
    'branch_id' => null, // Company-wide
    // ... other fields
]);
```

### 3. Validation Strategy
```php
public function validateAttendanceForBranch(Attendance $attendance, string $branchId): array
{
    // Get all applicable constraints (company-wide + branch-specific + inherited)
    $constraints = AttendanceConstraint::applicableToBranch($branchId)
        ->active()
        ->orderBy('priority', 'asc') // Apply in priority order
        ->get();
    
    $violations = [];
    foreach ($constraints as $constraint) {
        $violation = $this->validateSingleConstraint($attendance, $constraint);
        if ($violation) {
            $violations[] = $violation;
        }
    }
    
    return $violations;
}
```

## 📊 Monitoring Multi-Branch Constraints

### Dashboard Queries
```php
// Get constraint coverage across branches
$coverage = DB::table('attendance_constraints')
    ->select('branch_id', DB::raw('COUNT(*) as constraint_count'))
    ->where('company_id', $companyId)
    ->where('is_active', true)
    ->groupBy('branch_id')
    ->get();

// Get inheritance statistics
$inheritance = AttendanceConstraint::where('company_id', $companyId)
    ->where('inherit_from_parent', true)
    ->with('branch')
    ->get()
    ->groupBy('branch.parent_id');
```

### Performance Optimization
```php
// Cache constraints by branch
$branchConstraints = Cache::remember("constraints.branch.{$branchId}", 3600, function () use ($branchId) {
    return AttendanceConstraint::applicableToBranch($branchId)->get();
});

// Eager load relationships
$constraints = AttendanceConstraint::with(['branch', 'company'])
    ->applicableToBranch($branchId)
    ->get();
```

## 🚨 Troubleshooting Multi-Branch Constraints

### Common Issues

1. **Inheritance Not Working**
```php
// Check parent-child relationship
$branch = ManagementHierarchy::with('parent')->find($branchId);
if (!$branch->parent) {
    Log::warning('Branch has no parent for inheritance', ['branch_id' => $branchId]);
}
```

2. **Constraint Conflicts**
```php
// Check for conflicting constraints
$conflicts = AttendanceConstraint::applicableToBranch($branchId)
    ->where('type', 'time_multiple_periods')
    ->get();
    
if ($conflicts->count() > 1) {
    Log::warning('Multiple time constraints found', ['branch_id' => $branchId]);
}
```

3. **Performance Issues**
```php
// Use indexes for branch queries
Schema::table('attendance_constraints', function (Blueprint $table) {
    $table->index(['company_id', 'branch_id', 'is_active']);
    $table->index(['branch_id', 'inherit_from_parent']);
});
```

---

This guide demonstrates that the Attendance module already has comprehensive support for applying constraints to multiple branches through company-wide constraints, inheritance, and bulk operations. The system is designed to handle complex organizational hierarchies efficiently.
