# Attendance Module - Quick Reference

## 🚀 Quick Start

### 1. Create Multiple Periods Constraint
```php
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;

// Use factory method
$config = MultiplePeriodsConfig::standardOfficeHours();

// Or create custom
$config = new MultiplePeriodsConfig($weeklySchedule, 'Custom schedule');
```

### 2. Validate Attendance
```php
use Modules\Attendance\Services\AttendanceConstraintService;

$service = new AttendanceConstraintService();
$violations = $service->validateAttendance($attendance, $constraints);
```

### 3. API Usage
```bash
# Create constraint
curl -X POST /api/v1/attendance/constraints \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"name":"Office Hours","type":"time_multiple_periods","config":{...}}'

# Clock in
curl -X POST /api/v1/attendance/clock-in \
  -H "Authorization: Bearer {token}" \
  -d '{"employee_id":123}'
```

## 📋 Common Configurations

### Standard Office Hours (9-5, Mon-Fri)
```php
$config = MultiplePeriodsConfig::standardOfficeHours();
```

### Restaurant (Lunch & Dinner Service)
```php
$config = MultiplePeriodsConfig::restaurantServiceHours();
```

### 24/7 Security Shifts
```php
$config = MultiplePeriodsConfig::securityShifts();
```

### Flexible Office Hours
```php
$config = MultiplePeriodsConfig::flexibleOfficeHours();
```

## 🧪 Testing Commands

```bash
# Run all tests
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/

# Run specific test
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/TimePeriodTest.php

# Test with documentation
./vendor/bin/phpunit tests/Unit/Attendance/DataClasses/ --testdox
```

## 🔧 Data Classes Cheat Sheet

### TimePeriod
```php
$period = new TimePeriod('Morning', '09:00', '17:00', false, 15, 15);
$duration = $period->getDurationMinutes(); // 480
$effectiveStart = $period->getEffectiveStartTime(); // 08:45
```

### DaySchedule
```php
$day = new DaySchedule(true, [$period1, $period2]);
$totalMinutes = $day->getTotalWorkMinutes();
$periodNames = $day->getPeriodNames();
```

### WeeklySchedule
```php
$schedule = new WeeklySchedule(['monday' => $daySchedule]);
$enabledDays = $schedule->getEnabledDays();
$weeklyHours = $schedule->getTotalWeeklyWorkHours();
```

### MultiplePeriodsConfig
```php
$config = new MultiplePeriodsConfig($weeklySchedule, 'Description');
$json = $config->toJson();
$summary = $config->getSummary();
```

## 🛠️ Constraint Types

| Type | Description | Example Config |
|------|-------------|----------------|
| `location` | GPS/location based | `{"allowed_locations": [...]}` |
| `time` | Simple time windows | `{"start_time": "09:00", "end_time": "17:00"}` |
| `time_multiple_periods` | Complex scheduling | `{"weekly_schedule": {...}}` |
| `device` | Device/IP restrictions | `{"allowed_devices": [...]}` |
| `role` | Role-based rules | `{"role_rules": {...}}` |

## 📊 API Endpoints Summary

### Attendance
- `POST /attendance/clock-in` - Clock in employee
- `POST /attendance/clock-out` - Clock out employee  
- `GET /attendance` - Get attendance records

### Constraints
- `GET /attendance/constraints` - List constraints
- `POST /attendance/constraints` - Create constraint
- `PUT /attendance/constraints/{id}` - Update constraint
- `DELETE /attendance/constraints/{id}` - Delete constraint

### Violations
- `GET /attendance/violations` - List violations
- `PUT /attendance/violations/{id}/resolve` - Resolve violation

### Branch Management
- `GET /management-hierarchy/{id}/constraints` - Branch constraints
- `POST /management-hierarchy/{id}/constraints/bulk-update` - Bulk update

## ⚡ Performance Tips

1. **Use Factory Methods**: Pre-optimized configurations
2. **Cache Results**: Store configs in Redis
3. **Batch Operations**: Use bulk APIs
4. **Index Database**: Proper indexing on queries
5. **Monitor Memory**: Large configs need optimization

## 🐛 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Invalid time format | Use HH:MM 24-hour format |
| Overlapping periods | Check same-day period times |
| Cross-day errors | Set `spans_next_day: true` |
| Grace period issues | Use 0-1440 minutes range |
| JSON parsing errors | Validate JSON structure |

## 📝 Environment Variables

```env
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=attendance_db

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1

# Attendance specific
ATTENDANCE_CACHE_TTL=3600
ATTENDANCE_MAX_PERIODS=10
```

## 🔍 Debugging

```php
// Enable query logging
DB::enableQueryLog();

// Log constraint validation
Log::info('Validating constraint', ['constraint_id' => $id]);

// Check violations
$violations = $service->validateAttendance($attendance, $constraints);
Log::debug('Violations found', ['count' => count($violations)]);
```

## 📚 Key Files

```
modules/Attendance/
├── DataClasses/           # Core data structures
├── Services/             # Business logic
├── Controllers/          # API endpoints
├── Requests/            # Validation rules
├── Models/              # Database models
└── Database/migrations/ # Database schema

tests/
├── Unit/Attendance/DataClasses/    # Unit tests (92 tests)
└── Feature/Attendance/             # Integration tests

docs/
├── ATTENDANCE_MODULE_GUIDE.md      # Complete documentation
└── ATTENDANCE_QUICK_REFERENCE.md   # This file
```

---
*For complete documentation, see [ATTENDANCE_MODULE_GUIDE.md](./ATTENDANCE_MODULE_GUIDE.md)*
