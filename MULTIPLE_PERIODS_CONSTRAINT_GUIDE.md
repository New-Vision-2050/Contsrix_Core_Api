# Multiple Periods Constraint Guide

## Overview
The Multiple Periods constraint allows you to define multiple allowed time windows per day for employee attendance. This is perfect for shift-based work environments, flexible working hours, or businesses with multiple operational periods.

## Features
- ✅ Multiple time periods per day
- ✅ Cross-day periods (spans to next day)
- ✅ Grace periods for early/late clock-ins
- ✅ Day-specific configurations
- ✅ Flexible weekly schedules
- ✅ Comprehensive validation

## Configuration Structure

### Basic Configuration
```json
{
  "weekly_schedule": {
    "sunday": {
      "enabled": true,
      "periods": [
        {
          "name": "First Period",
          "start_time": "14:00",
          "end_time": "07:00",
          "spans_next_day": true,
          "grace_period_before": 15,
          "grace_period_after": 15
        },
        {
          "name": "Second Period", 
          "start_time": "13:00",
          "end_time": "18:00",
          "spans_next_day": false,
          "grace_period_before": 10,
          "grace_period_after": 10
        }
      ]
    },
    "monday": {
      "enabled": true,
      "periods": [
        {
          "name": "Morning Shift",
          "start_time": "08:00",
          "end_time": "16:00",
          "spans_next_day": false,
          "grace_period_before": 15,
          "grace_period_after": 15
        },
        {
          "name": "Evening Shift",
          "start_time": "16:00",
          "end_time": "00:00",
          "spans_next_day": true,
          "grace_period_before": 10,
          "grace_period_after": 10
        }
      ]
    },
    "tuesday": {
      "enabled": false
    }
  }
}
```

## Configuration Fields

### Day Configuration
- **enabled** (boolean, required): Whether attendance is allowed on this day
- **periods** (array, required if enabled): Array of time periods for the day

### Period Configuration
- **name** (string, required): Descriptive name for the period
- **start_time** (string, required): Start time in HH:MM format (24-hour)
- **end_time** (string, required): End time in HH:MM format (24-hour)
- **spans_next_day** (boolean, required): Whether the period continues to the next day
- **grace_period_before** (integer, optional): Minutes before start_time to allow clock-in
- **grace_period_after** (integer, optional): Minutes after end_time to allow clock-in

## Example Use Cases

### 1. Restaurant with Lunch and Dinner Service
```json
{
  "weekly_schedule": {
    "monday": {
      "enabled": true,
      "periods": [
        {
          "name": "Lunch Service",
          "start_time": "10:00",
          "end_time": "15:00",
          "spans_next_day": false,
          "grace_period_before": 30,
          "grace_period_after": 30
        },
        {
          "name": "Dinner Service",
          "start_time": "17:00",
          "end_time": "23:00",
          "spans_next_day": false,
          "grace_period_before": 30,
          "grace_period_after": 30
        }
      ]
    }
  }
}
```

### 2. 24/7 Security with Night Shifts
```json
{
  "weekly_schedule": {
    "sunday": {
      "enabled": true,
      "periods": [
        {
          "name": "Day Shift",
          "start_time": "06:00",
          "end_time": "18:00",
          "spans_next_day": false,
          "grace_period_before": 15,
          "grace_period_after": 15
        },
        {
          "name": "Night Shift",
          "start_time": "18:00",
          "end_time": "06:00",
          "spans_next_day": true,
          "grace_period_before": 15,
          "grace_period_after": 15
        }
      ]
    }
  }
}
```

### 3. Flexible Office Hours
```json
{
  "weekly_schedule": {
    "monday": {
      "enabled": true,
      "periods": [
        {
          "name": "Early Bird",
          "start_time": "07:00",
          "end_time": "15:00",
          "spans_next_day": false,
          "grace_period_before": 30,
          "grace_period_after": 30
        },
        {
          "name": "Standard Hours",
          "start_time": "09:00",
          "end_time": "17:00",
          "spans_next_day": false,
          "grace_period_before": 30,
          "grace_period_after": 30
        },
        {
          "name": "Late Start",
          "start_time": "11:00",
          "end_time": "19:00",
          "spans_next_day": false,
          "grace_period_before": 30,
          "grace_period_after": 30
        }
      ]
    }
  }
}
```

## API Usage

### Creating a Multiple Periods Constraint
```bash
POST /api/attendance/constraints
```

```json
{
  "constraint_type": "time",
  "constraint_name": "multiple_periods",
  "constraint_config": {
    "weekly_schedule": {
      "sunday": {
        "enabled": true,
        "periods": [
          {
            "name": "First Period",
            "start_time": "14:00",
            "end_time": "07:00",
            "spans_next_day": true,
            "grace_period_before": 15,
            "grace_period_after": 15
          },
          {
            "name": "Second Period",
            "start_time": "13:00",
            "end_time": "18:00",
            "spans_next_day": false,
            "grace_period_before": 10,
            "grace_period_after": 10
          }
        ]
      }
    }
  },
  "branch_id": 1,
  "priority": 5,
  "is_active": true,
  "inherit_from_parent": false,
  "notes": "Multiple periods for Sunday operations"
}
```

## Validation Logic

### Time Period Validation
1. **Day Check**: Verify the day is configured and enabled
2. **Period Check**: Ensure at least one period is defined for enabled days
3. **Time Format**: Validate HH:MM format for start and end times
4. **Grace Periods**: Validate non-negative numbers for grace periods
5. **Logical Validation**: For non-spanning periods, end time must be after start time

### Clock-in Validation
1. **Day Lookup**: Find configuration for the current day of week
2. **Period Matching**: Check if clock-in time falls within any allowed period
3. **Grace Period Application**: Apply before/after grace periods to extend valid windows
4. **Cross-day Handling**: Handle periods that span to the next day

## Violation Types

### High Severity Violations
- No schedule configured for the day
- Attendance not allowed on the day
- No periods configured for an enabled day

### Medium Severity Violations
- Clock-in time outside all allowed periods

## Benefits

### For Employees
- Clear understanding of allowed work periods
- Flexibility with multiple time options
- Grace periods for minor delays

### For Managers
- Precise control over work schedules
- Support for complex shift patterns
- Automatic violation detection

### For HR
- Compliance with labor regulations
- Accurate time tracking
- Detailed violation reporting

## Implementation Notes

### Performance Considerations
- Constraint validation runs on every clock-in
- Configuration is cached in JSON format
- Efficient time comparison algorithms

### Timezone Handling
- All times are processed in the user's timezone
- Cross-day periods handle timezone transitions
- Grace periods respect timezone boundaries

### Integration
- Works with existing branch-based constraints
- Supports constraint inheritance
- Compatible with all violation workflows

## Testing Examples

### Valid Clock-ins
```
Sunday 14:15 → Valid (First Period: 14:00-07:00 next day)
Sunday 13:30 → Valid (Second Period: 13:00-18:00)
Sunday 06:45 → Valid (First Period with grace period)
```

### Invalid Clock-ins
```
Sunday 12:00 → Invalid (Outside all periods)
Sunday 19:00 → Invalid (Outside all periods)
Tuesday 09:00 → Invalid (Day disabled)
```

## Migration and Deployment

### Database Impact
- No additional migrations required
- Uses existing constraint_config JSON field
- Backward compatible with existing constraints

### Rollout Strategy
1. Deploy code changes
2. Create test constraints
3. Validate with pilot users
4. Roll out to production branches
5. Monitor violation patterns

This multiple periods constraint provides the flexibility needed for modern workforce management while maintaining strict validation and reporting capabilities.
