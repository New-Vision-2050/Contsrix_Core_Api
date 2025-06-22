# Attendance Module - Complete User Guide

## Table of Contents
1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Attendance Constraints](#attendance-constraints)
4. [Multiple Periods Configuration](#multiple-periods-configuration)
5. [API Reference](#api-reference)
6. [Testing](#testing)
7. [Deployment](#deployment)
8. [Troubleshooting](#troubleshooting)

## Overview

The Attendance Module is a comprehensive employee attendance management system that provides:

- **Employee Clock-in/Clock-out**: Track employee attendance with precise timestamps
- **Attendance Constraints**: Flexible constraint system for validating attendance
- **Multiple Periods per Day**: Support for complex scheduling with multiple work periods
- **Branch-based Management**: Hierarchical attendance management across organizational branches
- **Violation Tracking**: Monitor and resolve attendance constraint violations
- **Reporting & Analytics**: Comprehensive attendance statistics and reports

### Key Features

✅ **Multi-tenant Support**: Company-level isolation and security  
✅ **Real-time Validation**: Pre and post attendance constraint validation  
✅ **Flexible Scheduling**: Support for 24/7, shift work, and complex schedules  
✅ **Branch Hierarchy**: Inherit constraints from parent branches  
✅ **Performance Optimized**: Efficient for high-volume attendance tracking  
✅ **RESTful API**: Complete API with comprehensive documentation  
✅ **Comprehensive Testing**: 92+ unit tests with full coverage  

## Architecture

### Core Components

```
Attendance Module
├── Models/
│   ├── Attendance.php              # Core attendance records
│   ├── AttendanceConstraint.php    # Constraint definitions
│   └── AttendanceConstraintViolation.php # Violation tracking
├── DataClasses/
│   ├── TimePeriod.php             # Single time period
│   ├── DaySchedule.php            # Daily schedule management
│   ├── WeeklySchedule.php         # Weekly schedule configuration
│   └── MultiplePeriodsConfig.php  # Complete periods configuration
├── Services/
│   └── AttendanceConstraintService.php # Business logic
├── Controllers/
│   ├── AttendanceController.php    # Attendance operations
│   ├── AttendanceConstraintController.php # Constraint management
│   └── ManagementHierarchyController.php # Branch operations
├── Requests/
│   ├── CreateAttendanceConstraintRequest.php
│   └── UpdateAttendanceConstraintRequest.php
└── Tests/
    ├── Unit/DataClasses/          # Data class unit tests
    └── Feature/                   # Integration tests
```

### Database Schema

#### Attendance Table
```sql
attendances
├── id (bigint, primary key)
├── employee_id (bigint, foreign key)
├── company_id (bigint, foreign key)
├── clock_in_time (timestamp)
├── clock_out_time (timestamp, nullable)
├── total_hours (decimal, nullable)
├── status (enum: present, absent, late, early_leave)
├── notes (text, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### Attendance Constraints Table
```sql
attendance_constraints
├── id (bigint, primary key)
├── company_id (bigint, foreign key)
├── branch_id (bigint, foreign key, nullable)
├── name (varchar)
├── type (enum: location, time, device, role, behavioral, security, compliance, time_multiple_periods)
├── config (json)
├── is_active (boolean)
├── inherit_from_parent (boolean)
├── created_at (timestamp)
└── updated_at (timestamp)
```

#### Constraint Violations Table
```sql
attendance_constraint_violations
├── id (bigint, primary key)
├── attendance_id (bigint, foreign key)
├── constraint_id (bigint, foreign key)
├── violation_type (varchar)
├── severity (enum: low, medium, high, critical)
├── description (text)
├── resolution_status (enum: pending, acknowledged, resolved, dismissed)
├── resolved_at (timestamp, nullable)
├── resolved_by (bigint, foreign key, nullable)
├── resolution_notes (text, nullable)
├── created_at (timestamp)
└── updated_at (timestamp)
```

## Attendance Constraints

### Constraint Types

#### 1. Location Constraints
Restrict attendance based on geographical location or specific premises.

```json
{
  "allowed_locations": [
    {
      "name": "Main Office",
      "latitude": 40.7128,
      "longitude": -74.0060,
      "radius": 100
    }
  ],
  "strict_mode": true
}
```

#### 2. Time Constraints
Control when employees can clock in/out.

```json
{
  "allowed_times": {
    "start_time": "08:00",
    "end_time": "18:00",
    "days": ["monday", "tuesday", "wednesday", "thursday", "friday"]
  },
  "grace_period": 15
}
```

#### 3. Device Constraints
Limit attendance to specific devices or IP addresses.

```json
{
  "allowed_devices": ["device_id_1", "device_id_2"],
  "allowed_ips": ["192.168.1.0/24"],
  "require_biometric": true
}
```

#### 4. Role-based Constraints
Apply different rules based on employee roles.

```json
{
  "role_rules": {
    "manager": {
      "flexible_hours": true,
      "overtime_allowed": true
    },
    "employee": {
      "strict_schedule": true,
      "overtime_requires_approval": true
    }
  }
}
```

#### 5. Multiple Periods per Day
Support complex scheduling with multiple work periods.

```json
{
  "weekly_schedule": {
    "monday": {
      "enabled": true,
      "periods": [
        {
          "name": "Morning Shift",
          "start_time": "09:00",
          "end_time": "13:00",
          "spans_next_day": false,
          "grace_period_before": 15,
          "grace_period_after": 15
        },
        {
          "name": "Evening Shift",
          "start_time": "14:00",
          "end_time": "18:00",
          "spans_next_day": false,
          "grace_period_before": 10,
          "grace_period_after": 10
        }
      ]
    }
  }
}
```

## Multiple Periods Configuration

### Data Classes

#### TimePeriod
Represents a single time period with validation and grace period support.

```php
use Modules\Attendance\DataClasses\TimePeriod;

// Create a time period
$period = new TimePeriod(
    name: 'Morning Shift',
    startTime: '09:00',
    endTime: '17:00',
    spansNextDay: false,
    gracePeriodBefore: 15,
    gracePeriodAfter: 15
);

// Get effective times with grace periods
$effectiveStart = $period->getEffectiveStartTime(); // 08:45
$effectiveEnd = $period->getEffectiveEndTime();     // 17:15

// Calculate duration
$duration = $period->getDurationMinutes(); // 480 minutes (8 hours)
```

#### DaySchedule
Manages multiple periods for a single day.

```php
use Modules\Attendance\DataClasses\DaySchedule;
use Modules\Attendance\DataClasses\TimePeriod;

// Create periods
$morning = new TimePeriod('Morning', '09:00', '13:00');
$evening = new TimePeriod('Evening', '14:00', '18:00');

// Create day schedule
$daySchedule = new DaySchedule(
    enabled: true,
    periods: [$morning, $evening]
);

// Add period
$daySchedule = $daySchedule->addPeriod($evening);

// Get total work time
$totalMinutes = $daySchedule->getTotalWorkMinutes(); // 480 minutes
```

#### WeeklySchedule
Manages the complete weekly schedule.

```php
use Modules\Attendance\DataClasses\WeeklySchedule;

// Create weekly schedule
$schedule = WeeklySchedule::standardWorkWeek();

// Check if day is enabled
$isEnabled = $schedule->isDayEnabled('monday'); // true

// Get enabled days
$enabledDays = $schedule->getEnabledDays(); // ['monday', 'tuesday', ...]

// Get total weekly hours
$weeklyHours = $schedule->getTotalWeeklyWorkHours(); // 40.0
```

#### MultiplePeriodsConfig
Main configuration class with factory methods.

```php
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;

// Use factory methods
$officeHours = MultiplePeriodsConfig::standardOfficeHours();
$restaurant = MultiplePeriodsConfig::restaurantServiceHours();
$security = MultiplePeriodsConfig::securityShifts();
$flexible = MultiplePeriodsConfig::flexibleOfficeHours();

// Create custom configuration
$config = new MultiplePeriodsConfig(
    weeklySchedule: $weeklySchedule,
    description: 'Custom schedule'
);

// JSON serialization
$json = $config->toJson();
$config = MultiplePeriodsConfig::fromJson($json);
```

### Factory Methods

#### Standard Office Hours
```php
$config = MultiplePeriodsConfig::standardOfficeHours();
// Monday-Friday: 09:00-17:00 with 15min grace periods
```

#### Restaurant Service Hours
```php
$config = MultiplePeriodsConfig::restaurantServiceHours();
// Lunch: 11:00-15:00, Dinner: 17:00-23:00
```

#### Security Shifts
```php
$config = MultiplePeriodsConfig::securityShifts();
// Day: 08:00-20:00, Night: 20:00-08:00 (cross-day)
```

#### Flexible Office Hours
```php
$config = MultiplePeriodsConfig::flexibleOfficeHours();
// Multiple time slots throughout the day
```

## API Reference

### Base URL
```
/api/v1/attendance
```

### Authentication
All endpoints require JWT authentication:
```http
Authorization: Bearer {jwt_token}
```

### Attendance Endpoints

#### Clock In
```http
POST /api/v1/attendance/clock-in
Content-Type: application/json

{
  "employee_id": 123,
  "location": {
    "latitude": 40.7128,
    "longitude": -74.0060
  },
  "device_info": {
    "device_id": "device_123",
    "ip_address": "192.168.1.100"
  }
}
```

#### Clock Out
```http
POST /api/v1/attendance/clock-out
Content-Type: application/json

{
  "attendance_id": 456,
  "location": {
    "latitude": 40.7128,
    "longitude": -74.0060
  }
}
```

#### Get Attendance Records
```http
GET /api/v1/attendance?employee_id=123&date_from=2024-01-01&date_to=2024-01-31
```

### Constraint Management

#### Create Constraint
```http
POST /api/v1/attendance/constraints
Content-Type: application/json

{
  "name": "Office Hours Multiple Periods",
  "type": "time_multiple_periods",
  "branch_id": 1,
  "config": {
    "weekly_schedule": {
      "monday": {
        "enabled": true,
        "periods": [
          {
            "name": "Morning Shift",
            "start_time": "09:00",
            "end_time": "13:00",
            "spans_next_day": false,
            "grace_period_before": 15,
            "grace_period_after": 15
          }
        ]
      }
    },
    "description": "Standard office hours with lunch break"
  },
  "is_active": true
}
```

#### Update Constraint
```http
PUT /api/v1/attendance/constraints/{id}
Content-Type: application/json

{
  "name": "Updated Constraint Name",
  "config": { /* updated config */ },
  "is_active": false
}
```

#### Get Constraints
```http
GET /api/v1/attendance/constraints?branch_id=1&type=time_multiple_periods
```

#### Delete Constraint
```http
DELETE /api/v1/attendance/constraints/{id}
```

### Violation Management

#### Get Violations
```http
GET /api/v1/attendance/violations?severity=high&status=pending
```

#### Resolve Violation
```http
PUT /api/v1/attendance/violations/{id}/resolve
Content-Type: application/json

{
  "resolution_notes": "Approved by manager",
  "resolution_status": "resolved"
}
```

### Branch Management

#### Get Branch Constraints
```http
GET /api/v1/management-hierarchy/{branch_id}/constraints
```

#### Bulk Update Branch Constraints
```http
POST /api/v1/management-hierarchy/{branch_id}/constraints/bulk-update
Content-Type: application/json

{
  "constraint_ids": [1, 2, 3],
  "action": "assign"
}
```

## Testing

### Running Tests

#### Unit Tests
```bash
# Run all unit tests
./vendor/bin/phpunit tests/Unit/Attendance/

# Run specific test class
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/TimePeriodTest.php

# Run with test documentation
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/ --testdox
```

#### Feature Tests
```bash
# Run feature tests (requires database setup)
./vendor/bin/phpunit tests/Feature/Attendance/
```

### Test Coverage

#### Data Classes (92 Unit Tests)
- **TimePeriod**: 18 tests covering validation, grace periods, cross-day support
- **DaySchedule**: 21 tests covering period management and overlap detection  
- **WeeklySchedule**: 25 tests covering weekly operations and factory methods
- **MultiplePeriodsConfig**: 28 tests covering configuration and JSON serialization

#### Test Categories
- ✅ **Validation Tests**: Error conditions and input validation
- ✅ **Edge Cases**: Midnight periods, cross-day spans, maximum values
- ✅ **Performance Tests**: Efficiency for production workloads
- ✅ **Immutability Tests**: Data integrity guarantees
- ✅ **JSON Tests**: API serialization/deserialization
- ✅ **Factory Tests**: Pre-built configuration validation

### Writing Custom Tests

```php
<?php

namespace Tests\Unit\Attendance\DataClasses;

use PHPUnit\Framework\TestCase;
use Modules\Attendance\DataClasses\TimePeriod;

class CustomTimePeriodTest extends TestCase
{
    public function test_custom_scenario()
    {
        $period = new TimePeriod('Test', '10:00', '14:00');
        
        $this->assertEquals('10:00', $period->startTime);
        $this->assertEquals('14:00', $period->endTime);
        $this->assertEquals(240, $period->getDurationMinutes());
    }
}
```

## Deployment

### Prerequisites
- PHP 8.2+
- Laravel 11.x
- MySQL 8.0+ or PostgreSQL 13+
- Redis (for caching)

### Installation Steps

1. **Install Dependencies**
```bash
composer install
```

2. **Run Migrations**
```bash
php artisan migrate
```

3. **Seed Database** (Optional)
```bash
php artisan db:seed --class=AttendanceSeeder
```

4. **Configure Environment**
```env
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=attendance_db
DB_USERNAME=username
DB_PASSWORD=password

CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

5. **Set Permissions**
```bash
# Laravel permissions
php artisan permission:create-permission "manage attendance constraints"
php artisan permission:create-permission "view attendance reports"
```

### Production Configuration

#### Performance Optimization
```php
// config/attendance.php
return [
    'cache_ttl' => 3600, // 1 hour
    'max_periods_per_day' => 10,
    'max_weekly_hours' => 168,
    'constraint_validation_timeout' => 30,
];
```

#### Monitoring
```php
// Monitor constraint violations
Log::info('Constraint violation detected', [
    'employee_id' => $employeeId,
    'constraint_id' => $constraintId,
    'violation_type' => $violationType
]);
```

## Troubleshooting

### Common Issues

#### 1. Time Zone Issues
```php
// Ensure consistent timezone handling
Carbon::setTestNow(Carbon::now('UTC'));
```

#### 2. Cross-day Period Validation
```php
// For periods spanning midnight
$period = new TimePeriod('Night Shift', '22:00', '06:00', true);
```

#### 3. Grace Period Calculations
```php
// Grace periods are in minutes
$period = new TimePeriod('Shift', '09:00', '17:00', false, 15, 15);
// Effective: 08:45 - 17:15
```

#### 4. JSON Serialization
```php
// Ensure proper JSON format
$config = MultiplePeriodsConfig::fromJson($jsonString);
if (!$config) {
    throw new InvalidArgumentException('Invalid JSON format');
}
```

### Error Codes

| Code | Description | Solution |
|------|-------------|----------|
| ATT001 | Invalid time format | Use HH:MM format (24-hour) |
| ATT002 | Overlapping periods | Ensure periods don't overlap on same day |
| ATT003 | Invalid grace period | Grace period must be 0-1440 minutes |
| ATT004 | Cross-day validation error | Set spans_next_day=true for overnight periods |
| ATT005 | Constraint violation | Check constraint configuration |

### Performance Tips

1. **Use Factory Methods**: Pre-optimized configurations
2. **Cache Configurations**: Store frequently used configs in Redis
3. **Batch Operations**: Use bulk APIs for multiple constraints
4. **Index Database**: Ensure proper indexing on frequently queried columns
5. **Monitor Memory**: Large configurations may require memory optimization

### Support

For technical support or feature requests:
- **Documentation**: Check this guide and API documentation
- **Tests**: Review unit tests for usage examples
- **Logs**: Check Laravel logs for detailed error information
- **Performance**: Use Laravel Telescope for request profiling

---

## Changelog

### v1.0.0 (2024-12-21)
- ✅ Initial release with complete attendance constraint system
- ✅ Multiple periods per day support
- ✅ Branch-based constraint management
- ✅ Comprehensive test suite (92 unit tests)
- ✅ Complete API documentation
- ✅ Factory methods for common configurations
- ✅ Performance optimization and caching

---

*This documentation covers the complete Attendance Module implementation. For the latest updates and API changes, please refer to the API documentation and test suite.*
