# Multiple Periods Data Classes Usage Guide

## Overview

The Multiple Periods constraint feature now uses a structured data class system that provides:

- **Type Safety**: Strict validation and type checking
- **Immutability**: Data classes are immutable for thread safety
- **Validation**: Comprehensive validation with clear error messages
- **Serialization**: JSON serialization/deserialization support
- **Factory Methods**: Pre-built configurations for common use cases

## Data Class Hierarchy

```
MultiplePeriodsConfig
└── WeeklySchedule
    └── DaySchedule (per day)
        └── TimePeriod[] (multiple periods per day)
```

## 1. TimePeriod Class

Represents a single time period within a day.

### Constructor

```php
use Modules\Attendance\DataClasses\TimePeriod;

$period = new TimePeriod(
    name: 'Morning Shift',
    startTime: '09:00',
    endTime: '17:00',
    extends_to_next_day: false,
    gracePeriodBefore: 15,  // minutes
    gracePeriodAfter: 15    // minutes
);
```

### Key Methods

```php
// Get duration in minutes (null for cross-day periods)
$duration = $period->getDurationMinutes(); // 480

// Get effective times including grace periods
$effectiveStart = $period->getEffectiveStartTime(); // '08:45'
$effectiveEnd = $period->getEffectiveEndTime();     // '17:15'

// Check overlap with another period
$overlaps = $period->overlapsWith($otherPeriod);

// Convert to array
$array = $period->toArray();

// Create from array
$period = TimePeriod::fromArray([
    'name' => 'Evening Shift',
    'start_time' => '17:00',
    'end_time' => '01:00',
    'spans_next_day' => true,
    'grace_period_before' => 30,
    'grace_period_after' => 30
]);
```

### Validation Rules

- **Name**: Required, non-empty, max 100 characters
- **Time Format**: HH:MM (24-hour format)
- **Grace Periods**: Non-negative integers, max 1440 minutes (24 hours)
- **Time Logic**: End time must be after start time for same-day periods

## 2. DaySchedule Class

Represents a day's schedule with multiple periods.

### Constructor

```php
use Modules\Attendance\DataClasses\DaySchedule;

// Enabled day with periods
$workDay = new DaySchedule(
    enabled: true,
    periods: [$morningShift, $afternoonShift]
);

// Disabled day
$weekend = new DaySchedule(enabled: false);
```

### Factory Methods

```php
// Create enabled day
$workDay = DaySchedule::enabled($period1, $period2, $period3);

// Create disabled day
$weekend = DaySchedule::disabled();
```

### Key Methods

```php
// Get period information
$periodCount = $daySchedule->getPeriodCount();
$periodNames = $daySchedule->getPeriodNames();
$totalMinutes = $daySchedule->getTotalWorkMinutes();

// Period management
$period = $daySchedule->getPeriod('Morning Shift');
$newSchedule = $daySchedule->addPeriod($newPeriod);
$newSchedule = $daySchedule->removePeriod('Old Period');

// Checks
$hasCrossDay = $daySchedule->hasCrossDayPeriods();
```

### Validation Rules

- **Enabled Days**: Must have at least one period
- **Disabled Days**: Cannot have any periods
- **Period Names**: Must be unique within the day
- **Overlaps**: Same-day periods cannot overlap

## 3. WeeklySchedule Class

Represents a complete weekly schedule.

### Constructor

```php
use Modules\Attendance\DataClasses\WeeklySchedule;

$schedule = new WeeklySchedule([
    'monday' => $mondaySchedule,
    'tuesday' => $tuesdaySchedule,
    // ... other days
]);
```

### Factory Methods

```php
// Standard 5-day work week
$workPeriod = new TimePeriod('Work', '09:00', '17:00');
$schedule = WeeklySchedule::standardWorkWeek($workPeriod);

// 24/7 operations
$dayShift = new TimePeriod('Day', '06:00', '18:00');
$nightShift = new TimePeriod('Night', '18:00', '06:00', true);
$schedule = WeeklySchedule::twentyFourSeven($dayShift, $nightShift);
```

### Key Methods

```php
// Day management
$daySchedule = $weeklySchedule->getDaySchedule('monday');
$newSchedule = $weeklySchedule->setDaySchedule('friday', $fridaySchedule);
$newSchedule = $weeklySchedule->removeDaySchedule('saturday');

// Information
$configuredDays = $weeklySchedule->getConfiguredDays();
$enabledDays = $weeklySchedule->getEnabledDays();
$disabledDays = $weeklySchedule->getDisabledDays();

// Checks
$isEnabled = $weeklySchedule->isDayEnabled('monday');
$hasDay = $weeklySchedule->hasDaySchedule('sunday');
$hasCrossDay = $weeklySchedule->hasCrossDayPeriods();

// Statistics
$totalPeriods = $weeklySchedule->getTotalPeriodCount();
$weeklyHours = $weeklySchedule->getTotalWeeklyWorkHours();
$allPeriods = $weeklySchedule->getAllPeriodNames();

// Validation
$issues = $weeklySchedule->validate(); // Returns array of validation issues
```

## 4. MultiplePeriodsConfig Class

Main configuration class for the constraint.

### Constructor

```php
use Modules\Attendance\DataClasses\MultiplePeriodsConfig;

$config = new MultiplePeriodsConfig(
    weeklySchedule: $weeklySchedule,
    description: 'Custom work schedule',
    metadata: ['version' => '1.0', 'created_by' => 'admin']
);
```

### Factory Methods

```php
// Standard office hours (9-5, Mon-Fri)
$config = MultiplePeriodsConfig::standardOfficeHours();

// Restaurant service hours
$config = MultiplePeriodsConfig::restaurantServiceHours();

// 24/7 security shifts
$config = MultiplePeriodsConfig::securityShifts();

// Flexible office hours
$config = MultiplePeriodsConfig::flexibleOfficeHours();

// Custom parameters
$config = MultiplePeriodsConfig::standardOfficeHours(
    startTime: '08:00',
    endTime: '16:00',
    gracePeriod: 30
);
```

### JSON Serialization

```php
// To JSON
$json = $config->toJson();
$json = $config->toJson(JSON_COMPACT); // Without pretty printing

// From JSON
$config = MultiplePeriodsConfig::fromJson($jsonString);

// Array conversion
$array = $config->toArray();
$config = MultiplePeriodsConfig::fromArray($arrayData);

// JsonSerializable support
$json = json_encode($config); // Automatically uses jsonSerialize()
```

### Key Methods

```php
// Quick access methods
$daySchedule = $config->getDaySchedule('monday');
$isEnabled = $config->isDayEnabled('tuesday');
$enabledDays = $config->getEnabledDays();

// Statistics
$totalPeriods = $config->getTotalPeriodCount();
$weeklyHours = $config->getTotalWeeklyWorkHours();
$hasCrossDay = $config->hasCrossDayPeriods();

// Immutable updates
$newConfig = $config->withDescription('Updated description');
$newConfig = $config->withMetadata(['version' => '2.0']);
$newConfig = $config->withWeeklySchedule($newWeeklySchedule);

// Summary
$summary = $config->getSummary();
```

## Usage in Request Validation

### Before (Manual Validation)

```php
protected function validateMultiplePeriodsConfig($validator, array $config): void
{
    // 100+ lines of manual validation logic
    if (!isset($config['weekly_schedule'])) {
        $validator->errors()->add('constraint_config.weekly_schedule', 'Required');
    }
    // ... many more validation rules
}
```

### After (Data Class Validation)

```php
protected function validateMultiplePeriodsConfig($validator, array $config): void
{
    try {
        $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);
        
        // Additional business logic validation
        if ($multiplePeriodsConfig->getTotalWeeklyWorkHours() > 80) {
            $validator->errors()->add('constraint_config', 'Weekly hours exceed limit');
        }
        
    } catch (InvalidArgumentException $e) {
        $validator->errors()->add('constraint_config', $e->getMessage());
    }
}
```

## Usage in Service Layer

### Before (Manual Parsing)

```php
protected function validateMultiplePeriods(Attendance $attendance, array $config): ?array
{
    $weeklySchedule = $config['weekly_schedule'] ?? [];
    $dayOfWeek = strtolower($attendance->clock_in_time->format('l'));
    
    if (!isset($weeklySchedule[$dayOfWeek])) {
        return ['error' => 'Day not configured'];
    }
    
    $dayConfig = $weeklySchedule[$dayOfWeek];
    // ... manual parsing and validation
}
```

### After (Data Class Usage)

```php
protected function validateMultiplePeriods(Attendance $attendance, array $config): ?array
{
    try {
        $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);
    } catch (InvalidArgumentException $e) {
        return ['error' => $e->getMessage()];
    }
    
    $dayOfWeek = strtolower($attendance->clock_in_time->format('l'));
    $daySchedule = $multiplePeriodsConfig->getDaySchedule($dayOfWeek);
    
    if (!$daySchedule || !$daySchedule->enabled) {
        return ['error' => 'Day not enabled'];
    }
    
    // Clean, type-safe validation logic
    foreach ($daySchedule->periods as $period) {
        if ($this->isTimeWithinPeriod($clockInTime, $period)) {
            return null; // Valid
        }
    }
    
    return ['error' => 'Outside allowed periods'];
}
```

## API Usage Examples

### Creating a Configuration

```php
// POST /api/attendance/constraints
{
    "constraint_type": "time",
    "constraint_name": "multiple_periods",
    "constraint_config": {
        "weekly_schedule": {
            "monday": {
                "enabled": true,
                "periods": [
                    {
                        "name": "Morning Shift",
                        "start_time": "09:00",
                        "end_time": "17:00",
                        "spans_next_day": false,
                        "grace_period_before": 15,
                        "grace_period_after": 15
                    }
                ]
            },
            "sunday": {
                "enabled": false
            }
        },
        "description": "Standard office hours",
        "metadata": {
            "department": "Engineering",
            "policy_version": "2024.1"
        }
    }
}
```

### Validation Response

```php
// Success
{
    "success": true,
    "message": "Constraint created successfully",
    "data": {
        "id": "uuid",
        "constraint_config": { /* validated config */ }
    }
}

// Validation Error
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "constraint_config": [
            "Multiple periods configuration error: Period name cannot be empty"
        ]
    }
}
```

## Best Practices

### 1. Use Factory Methods When Possible

```php
// Good
$config = MultiplePeriodsConfig::standardOfficeHours();

// Avoid manual construction for common patterns
$period = new TimePeriod('Work', '09:00', '17:00');
$day = DaySchedule::enabled($period);
$week = new WeeklySchedule(['monday' => $day, /* ... */]);
$config = new MultiplePeriodsConfig($week);
```

### 2. Handle Exceptions Properly

```php
try {
    $config = MultiplePeriodsConfig::fromArray($userInput);
} catch (InvalidArgumentException $e) {
    // Log the error and return user-friendly message
    Log::warning('Invalid constraint config', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Invalid schedule configuration']);
}
```

### 3. Use Immutable Updates

```php
// Good - immutable
$updatedConfig = $config->withDescription('New description');

// Avoid - would require mutable objects
// $config->setDescription('New description');
```

### 4. Leverage Type Safety

```php
// Type hints ensure correct usage
function processSchedule(MultiplePeriodsConfig $config): array
{
    return $config->getSummary();
}

// IDE will provide autocomplete and catch errors
$hours = $config->getTotalWeeklyWorkHours(); // float
$days = $config->getEnabledDays();           // array
```

### 5. Performance Considerations

```php
// Cache parsed configurations
$configCache = Cache::remember("constraint_config_{$id}", 3600, function() use ($rawConfig) {
    return MultiplePeriodsConfig::fromArray($rawConfig);
});

// Avoid repeated parsing in loops
$config = MultiplePeriodsConfig::fromArray($rawConfig);
foreach ($attendanceRecords as $record) {
    $this->validateAgainstConfig($record, $config); // Pass parsed config
}
```

## Error Handling

### Common Validation Errors

1. **Invalid Time Format**: "Invalid start time format: 25:00. Expected HH:MM"
2. **Empty Period Name**: "Period name cannot be empty"
3. **Overlapping Periods**: "Periods 'Morning' and 'Afternoon' overlap"
4. **Invalid Day**: "Invalid day: invalidday. Must be one of: sunday, monday, ..."
5. **Disabled Day with Periods**: "Disabled days cannot have periods"
6. **Enabled Day without Periods**: "Enabled days must have at least one period"

### Exception Hierarchy

```
InvalidArgumentException
├── Time format errors
├── Period validation errors
├── Day configuration errors
├── Schedule validation errors
└── Configuration structure errors
```

## Migration from Old System

### Step 1: Update Request Validation

Replace manual validation with data class validation:

```php
// Old
protected function validateMultiplePeriodsConfig($validator, array $config): void
{
    // 100+ lines of manual validation
}

// New
protected function validateMultiplePeriodsConfig($validator, array $config): void
{
    try {
        MultiplePeriodsConfig::fromArray($config);
    } catch (InvalidArgumentException $e) {
        $validator->errors()->add('constraint_config', $e->getMessage());
    }
}
```

### Step 2: Update Service Methods

Replace manual array parsing with data classes:

```php
// Old
protected function validateMultiplePeriods(Attendance $attendance, array $config): ?array
{
    $weeklySchedule = $config['weekly_schedule'] ?? [];
    // Manual parsing...
}

// New
protected function validateMultiplePeriods(Attendance $attendance, array $config): ?array
{
    $multiplePeriodsConfig = MultiplePeriodsConfig::fromArray($config);
    // Type-safe operations...
}
```

### Step 3: Update Tests

Use data classes in tests for better maintainability:

```php
// Old
$config = [
    'weekly_schedule' => [
        'monday' => [
            'enabled' => true,
            'periods' => [/* complex array structure */]
        ]
    ]
];

// New
$config = MultiplePeriodsConfig::standardOfficeHours()->toArray();
```

This data class system provides a robust, type-safe foundation for the Multiple Periods constraint feature while maintaining backward compatibility with existing JSON configurations.
