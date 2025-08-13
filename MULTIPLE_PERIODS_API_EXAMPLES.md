# Multiple Periods Constraint API Examples

## Overview
This document provides practical API examples for using the new Multiple Periods Per Day constraint feature in the Employee Attendance System.

## API Endpoint
```
POST /api/attendance/constraints
```

## Example 1: Restaurant with Lunch and Dinner Service

### Request
```json
{
  "constraint_type": "time",
  "constraint_name": "multiple_periods",
  "constraint_config": {
    "weekly_schedule": {
      "monday": {
        "enabled": true,
        "periods": [
          {
            "name": "Lunch Prep & Service",
            "start_time": "10:00",
            "end_time": "15:30",
            "spans_next_day": false,
            "grace_period_before": 30,
            "grace_period_after": 30
          },
          {
            "name": "Dinner Prep & Service",
            "start_time": "16:30",
            "end_time": "23:00",
            "spans_next_day": false,
            "grace_period_before": 30,
            "grace_period_after": 30
          }
        ]
      },
      "tuesday": {
        "enabled": true,
        "periods": [
          {
            "name": "Lunch Prep & Service",
            "start_time": "10:00",
            "end_time": "15:30",
            "spans_next_day": false,
            "grace_period_before": 30,
            "grace_period_after": 30
          },
          {
            "name": "Dinner Prep & Service",
            "start_time": "16:30",
            "end_time": "23:00",
            "spans_next_day": false,
            "grace_period_before": 30,
            "grace_period_after": 30
          }
        ]
      },
      "wednesday": {
        "enabled": false
      }
    }
  },
  "branch_id": 1,
  "priority": 5,
  "is_active": true,
  "inherit_from_parent": false,
  "notes": "Restaurant service periods with prep time"
}
```

### Response
```json
{
  "success": true,
  "message": "Attendance constraint created successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "constraint_type": "time",
    "constraint_name": "multiple_periods",
    "constraint_config": {
      "weekly_schedule": {
        "monday": {
          "enabled": true,
          "periods": [
            {
              "name": "Lunch Prep & Service",
              "start_time": "10:00",
              "end_time": "15:30",
              "spans_next_day": false,
              "grace_period_before": 30,
              "grace_period_after": 30
            },
            {
              "name": "Dinner Prep & Service",
              "start_time": "16:30",
              "end_time": "23:00",
              "spans_next_day": false,
              "grace_period_before": 30,
              "grace_period_after": 30
            }
          ]
        }
      }
    },
    "branch_id": 1,
    "priority": 5,
    "is_active": true,
    "created_at": "2024-12-21T10:00:00Z"
  }
}
```

## Example 2: 24/7 Security Operations

### Request
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
      },
      "monday": {
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
  },
  "branch_id": 2,
  "priority": 8,
  "is_active": true,
  "inherit_from_parent": false,
  "notes": "24/7 security coverage with day and night shifts"
}
```

## Example 3: Flexible Office Hours

### Request
```json
{
  "constraint_type": "time",
  "constraint_name": "multiple_periods",
  "constraint_config": {
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
      },
      "friday": {
        "enabled": true,
        "periods": [
          {
            "name": "Friday Flex",
            "start_time": "08:00",
            "end_time": "16:00",
            "spans_next_day": false,
            "grace_period_before": 45,
            "grace_period_after": 45
          }
        ]
      },
      "saturday": {
        "enabled": false
      },
      "sunday": {
        "enabled": false
      }
    }
  },
  "branch_id": 3,
  "priority": 3,
  "is_active": true,
  "inherit_from_parent": false,
  "notes": "Flexible working hours with multiple options"
}
```

## Example 4: Healthcare Shifts

### Request
```json
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
            "start_time": "07:00",
            "end_time": "15:00",
            "spans_next_day": false,
            "grace_period_before": 10,
            "grace_period_after": 10
          },
          {
            "name": "Evening Shift",
            "start_time": "15:00",
            "end_time": "23:00",
            "spans_next_day": false,
            "grace_period_before": 10,
            "grace_period_after": 10
          },
          {
            "name": "Night Shift",
            "start_time": "23:00",
            "end_time": "07:00",
            "spans_next_day": true,
            "grace_period_before": 10,
            "grace_period_after": 10
          }
        ]
      }
    }
  },
  "branch_id": 4,
  "priority": 10,
  "is_active": true,
  "inherit_from_parent": false,
  "notes": "Healthcare 24/7 coverage with overlapping shifts"
}
```

## Validation Examples

### Valid Clock-in Times
```json
// For Restaurant Example (Monday):
// ✅ 09:30 - Valid (Lunch period with grace)
// ✅ 10:15 - Valid (Lunch period)
// ✅ 16:00 - Valid (Dinner period with grace)
// ✅ 22:30 - Valid (Dinner period)

// For Security Example (Sunday Night Shift):
// ✅ 18:15 - Valid (Night shift start)
// ✅ 02:00 - Valid (Night shift continues next day)
// ✅ 05:45 - Valid (Night shift with grace)
```

### Invalid Clock-in Times (Violations)
```json
// For Restaurant Example (Monday):
// ❌ 08:00 - Invalid (Before lunch period)
// ❌ 16:00 - Invalid (Between periods)
// ❌ 01:00 - Invalid (After dinner period)

// For Flexible Office (Saturday):
// ❌ 09:00 - Invalid (Day disabled)

// Violation Response:
{
  "success": false,
  "message": "Clock-in time outside allowed periods for monday",
  "violation": {
    "constraint_type": "time",
    "violation_type": "time_violation",
    "severity": "medium",
    "details": {
      "day_of_week": "monday",
      "clock_in_time": "16:00",
      "allowed_periods": [
        {
          "name": "Lunch Prep & Service",
          "start_time": "10:00",
          "end_time": "15:30",
          "spans_next_day": false
        },
        {
          "name": "Dinner Prep & Service",
          "start_time": "16:30",
          "end_time": "23:00",
          "spans_next_day": false
        }
      ]
    }
  }
}
```

## Configuration Guidelines

### Time Format
- Use 24-hour format: `HH:MM` (e.g., "14:30", "09:00")
- No seconds or timezone information needed

### Grace Periods
- Specified in minutes
- `grace_period_before`: Minutes before start_time to allow clock-in
- `grace_period_after`: Minutes after end_time to allow clock-in
- Both are optional (default: 0)

### Cross-day Periods
- Set `spans_next_day: true` for periods crossing midnight
- Example: Night shift 22:00 to 06:00 next day
- The period continues until the end_time on the following day

### Day Configuration
- Days: `sunday`, `monday`, `tuesday`, `wednesday`, `thursday`, `friday`, `saturday`
- Case-insensitive
- Set `enabled: false` to disable attendance on specific days
- Each enabled day must have at least one period

### Best Practices
1. **Avoid Overlapping Periods**: While supported, overlapping periods may cause confusion
2. **Use Descriptive Names**: Period names help employees understand schedules
3. **Set Appropriate Grace Periods**: Balance flexibility with policy enforcement
4. **Test Cross-day Logic**: Verify overnight periods work as expected
5. **Document Special Cases**: Note any unique scheduling requirements

## Integration Notes

### Frontend Integration
- Display period names in employee schedules
- Show grace periods in clock-in interfaces
- Highlight valid time windows
- Provide clear violation messages

### Mobile App Integration
- Cache constraint configurations for offline validation
- Show current valid periods in real-time
- Provide countdown timers for period boundaries
- Send notifications for upcoming periods

### Reporting Integration
- Track period utilization statistics
- Generate shift coverage reports
- Monitor grace period usage
- Analyze violation patterns by period

This multiple periods constraint provides the flexibility needed for complex scheduling requirements while maintaining strict validation and comprehensive violation tracking.
