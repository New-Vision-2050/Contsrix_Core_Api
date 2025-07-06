# Single Constraint for Multiple Branches Guide

## Overview

The updated AttendanceConstraint system now supports storing **multiple branch IDs in a single constraint record** using the `branch_ids` JSON array field. This eliminates the need to create separate constraint records for each branch.

## 🔄 **Key Changes Made**

### **Database Schema Update**
- **Changed**: `branch_id` (single UUID) → `branch_ids` (JSON array)
- **Migration**: Automatically converts existing single branch_id to array format
- **Backward Compatible**: Existing data is preserved and migrated

### **Model Updates**
- `AttendanceConstraint` model now uses `branch_ids` array
- New helper methods: `appliesToBranch()`, `addBranch()`, `removeBranch()`
- Updated query scopes for JSON array operations
- Enhanced relationship methods

### **API Changes**
- Request validation now accepts `branch_ids` array instead of single `branch_id`
- DTOs updated to handle array of branch IDs
- All endpoints now support multiple branches in single request

## 🚀 **How to Use Multi-Branch Constraints**

### **1. Create Constraint for Multiple Branches**

#### API Request
```http
POST /api/v1/attendance/constraints
Content-Type: application/json
Authorization: Bearer {jwt_token}

{
  "name": "Multi-Branch Office Hours",
  "type": "time_multiple_periods",
  "branch_ids": [
    "branch-uuid-1",
    "branch-uuid-2", 
    "branch-uuid-3"
  ],
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
      // ... other days
    }
  },
  "is_active": true
}
```

#### Programmatic Creation
```php
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;

// Create constraint for multiple branches
$constraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'Multi-Branch Office Hours',
    'type' => 'time_multiple_periods',
    'branch_ids' => [
        'branch-uuid-1',
        'branch-uuid-2',
        'branch-uuid-3'
    ],
    'config' => MultiplePeriodsConfig::standardOfficeHours()->toArray(),
    'is_active' => true
]);
```

### **2. Company-wide Constraints (All Branches)**

```php
// Apply to ALL branches by setting branch_ids to null or empty array
$constraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'Company-wide Policy',
    'type' => 'time_multiple_periods',
    'branch_ids' => null, // or [] - applies to all branches
    'config' => $config,
    'is_active' => true
]);
```

### **3. Dynamic Branch Management**

#### Add Branch to Existing Constraint
```php
$constraint = AttendanceConstraint::find($constraintId);

// Add a new branch
$constraint->addBranch('new-branch-uuid');

// Check if constraint applies to specific branch
if ($constraint->appliesToBranch('branch-uuid-1')) {
    echo "Constraint applies to this branch";
}

// Remove a branch
$constraint->removeBranch('branch-uuid-2');
```

#### API Endpoints for Branch Management
```http
# Add branch to constraint
POST /api/v1/attendance/constraints/{constraint-id}/branches
{
  "branch_id": "new-branch-uuid"
}

# Remove branch from constraint  
DELETE /api/v1/attendance/constraints/{constraint-id}/branches/{branch-id}

# Get all branches for constraint
GET /api/v1/attendance/constraints/{constraint-id}/branches
```

## 📋 **Real-World Examples**

### **Example 1: Restaurant Chain with Regional Schedules**

```php
// Create constraint for all East Coast restaurants
$eastCoastConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'East Coast Restaurant Hours',
    'type' => 'time_multiple_periods',
    'branch_ids' => [
        'restaurant-nyc-uuid',
        'restaurant-boston-uuid',
        'restaurant-philly-uuid',
        'restaurant-dc-uuid'
    ],
    'config' => [
        'weekly_schedule' => [
            'monday' => [
                'enabled' => true,
                'periods' => [
                    [
                        'name' => 'Lunch Service',
                        'start_time' => '11:00',
                        'end_time' => '15:00',
                        'spans_next_day' => false,
                        'grace_period_before' => 10,
                        'grace_period_after' => 10
                    ],
                    [
                        'name' => 'Dinner Service', 
                        'start_time' => '17:00',
                        'end_time' => '23:00',
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

// Separate constraint for West Coast (different time zone)
$westCoastConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'West Coast Restaurant Hours',
    'type' => 'time_multiple_periods',
    'branch_ids' => [
        'restaurant-la-uuid',
        'restaurant-sf-uuid',
        'restaurant-seattle-uuid'
    ],
    'config' => [
        'weekly_schedule' => [
            'monday' => [
                'enabled' => true,
                'periods' => [
                    [
                        'name' => 'Lunch Service',
                        'start_time' => '11:30',
                        'end_time' => '15:30',
                        'spans_next_day' => false,
                        'grace_period_before' => 10,
                        'grace_period_after' => 10
                    ],
                    [
                        'name' => 'Dinner Service',
                        'start_time' => '17:30',
                        'end_time' => '23:30',
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

### **Example 2: Security Company with 24/7 Operations**

```php
// Create constraint for all security branches
$securityConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => '24/7 Security Operations',
    'type' => 'time_multiple_periods',
    'branch_ids' => [
        'security-downtown-uuid',
        'security-airport-uuid',
        'security-mall-uuid',
        'security-hospital-uuid'
    ],
    'config' => MultiplePeriodsConfig::securityShifts()->toArray(),
    'is_active' => true
]);
```

### **Example 3: Office Branches with Flexible Hours**

```php
// Create constraint for office branches
$officeConstraint = AttendanceConstraint::create([
    'company_id' => $companyId,
    'name' => 'Flexible Office Hours',
    'type' => 'time_multiple_periods',
    'branch_ids' => [
        'office-hq-uuid',
        'office-branch1-uuid',
        'office-branch2-uuid'
    ],
    'config' => MultiplePeriodsConfig::flexibleOfficeHours()->toArray(),
    'is_active' => true
]);
```

## 🔍 **Querying Multi-Branch Constraints**

### **Find Constraints for Specific Branch**
```php
// Get all constraints that apply to a specific branch
$branchConstraints = AttendanceConstraint::forBranch('branch-uuid-1')->get();

// Get constraints including inherited ones
$applicableConstraints = AttendanceConstraint::applicableToBranch('branch-uuid-1')->get();

// Check if specific constraint applies to branch
$constraint = AttendanceConstraint::find($constraintId);
if ($constraint->appliesToBranch('branch-uuid-1')) {
    // Apply constraint logic
}
```

### **Get All Branches for Constraint**
```php
$constraint = AttendanceConstraint::find($constraintId);

// Get branch models
$branches = $constraint->branches();

// Get branch IDs array
$branchIds = $constraint->branch_ids;

// Check if company-wide
if (empty($constraint->branch_ids)) {
    echo "This is a company-wide constraint";
}
```

### **Database Queries**
```sql
-- Find constraints for specific branch
SELECT * FROM attendance_constraints 
WHERE JSON_CONTAINS(branch_ids, '"branch-uuid-1"') 
   OR branch_ids IS NULL;

-- Find all multi-branch constraints
SELECT * FROM attendance_constraints 
WHERE JSON_LENGTH(branch_ids) > 1;

-- Find company-wide constraints
SELECT * FROM attendance_constraints 
WHERE branch_ids IS NULL;
```

## 🎯 **Benefits of Single Constraint Approach**

### **✅ Advantages**
1. **Reduced Database Records**: One constraint instead of N constraints for N branches
2. **Easier Management**: Update one record to affect multiple branches
3. **Better Performance**: Fewer database queries and joins
4. **Simplified Logic**: Single constraint validation per rule type
5. **Atomic Updates**: Changes apply to all branches simultaneously
6. **Reduced Complexity**: No need for complex synchronization logic

### **📊 Performance Comparison**

#### **Before (Separate Constraints)**
```
10 branches = 10 constraint records
100 branches = 100 constraint records
Database queries: O(n) where n = number of branches
```

#### **After (Single Multi-Branch Constraint)**
```
10 branches = 1 constraint record
100 branches = 1 constraint record  
Database queries: O(1) regardless of branch count
```

## 🔧 **Migration Guide**

### **Automatic Migration**
The migration automatically converts existing data:

```sql
-- Before migration
branch_id: "branch-uuid-1"

-- After migration  
branch_ids: ["branch-uuid-1"]
```

### **Manual Migration for Complex Cases**
```php
// If you have multiple constraints for the same branches that should be combined
$duplicateConstraints = AttendanceConstraint::where('company_id', $companyId)
    ->where('type', 'time_multiple_periods')
    ->where('config', $sameConfig)
    ->get()
    ->groupBy('config');

foreach ($duplicateConstraints as $config => $constraints) {
    if ($constraints->count() > 1) {
        // Combine branch_ids from all constraints
        $allBranchIds = $constraints->pluck('branch_ids')->flatten()->unique()->values();
        
        // Keep first constraint, update with all branch IDs
        $firstConstraint = $constraints->first();
        $firstConstraint->update(['branch_ids' => $allBranchIds->toArray()]);
        
        // Delete duplicate constraints
        $constraints->skip(1)->each->delete();
    }
}
```

## 🚨 **Important Considerations**

### **1. Validation**
- All branch IDs must exist in `management_hierarchies` table
- All branch IDs must belong to the same company
- Maximum recommended branches per constraint: 100 (for performance)

### **2. Performance**
- JSON queries are optimized with database indexes
- Consider caching for frequently accessed constraints
- Monitor query performance with large branch arrays

### **3. Inheritance**
- Inheritance still works with parent-child branch relationships
- Multi-branch constraints can be inherited by child branches
- Priority system resolves conflicts between inherited and direct constraints

### **4. Backup Strategy**
- Always backup before running the migration
- Test migration on staging environment first
- Verify data integrity after migration

## 📚 **API Documentation Updates**

### **Updated Endpoints**

#### **Create Constraint**
```http
POST /api/v1/attendance/constraints
{
  "name": "Multi-Branch Constraint",
  "type": "time_multiple_periods", 
  "branch_ids": ["uuid1", "uuid2", "uuid3"], // NEW: Array instead of single ID
  "config": { /* constraint config */ }
}
```

#### **Update Constraint**
```http
PUT /api/v1/attendance/constraints/{id}
{
  "branch_ids": ["uuid1", "uuid2", "uuid4"] // Add/remove branches
}
```

#### **New Branch Management Endpoints**
```http
# Add branch to constraint
POST /api/v1/attendance/constraints/{id}/branches
{ "branch_id": "new-uuid" }

# Remove branch from constraint
DELETE /api/v1/attendance/constraints/{id}/branches/{branch-id}

# List constraint branches
GET /api/v1/attendance/constraints/{id}/branches
```

---

This updated system provides a much more efficient and manageable approach to multi-branch constraints while maintaining all existing functionality and adding powerful new capabilities for branch management.
