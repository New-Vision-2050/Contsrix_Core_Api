# Frontend guide: Attendance constraint rules (all minutes)

**Endpoint**
- `GET  /api/v1/attendance/constraints/{constraintId}/rules`
- `PATCH /api/v1/attendance/constraints/{constraintId}/rules`

**Breaking change:** every duration on this API is **minutes**, never hours.

---

## Units (important)

| Field | Unit | Example | Meaning |
|---|---|---|---|
| `early_clock_in_minutes` | minutes | `30` | Can clock in 30 minutes before shift start |
| `can_clock_in_before` | minutes | `120` | First clock-in deadline = shift start + 120 minutes |
| `extension_minutes` | minutes | `120` | Can finish / re-clock-in for 120 minutes after shift end |
| `extension_hours_shift` | minutes (alias) | `120` | Same as `extension_minutes` — **not hours** |
| `max_over_time` | minutes | `0` | Max overtime beyond the work window |
| `out_zone_minutes` | minutes | `20` | Allowed time outside geofence |
| `working_hours` | hours | `9` | Display/cap only — still hours (shift length UI) |
| `max_working_hours` | hours | `9` | Cap — still hours |

Boolean overtime flags are not durations.

---

## PATCH body (preferred)

Send flat fields. Prefer `extension_minutes` over the alias.

```json
{
  "can_clock_in_before": 120,
  "early_clock_in_minutes": 30,
  "extension_minutes": 120,
  "max_over_time": 0,
  "out_zone_minutes": 20,
  "is_overtime_before_early_clock_in": false,
  "is_overtime_after_extension_hours_shift": false,
  "is_after_finish_working_hours": false
}
```

### Accepted aliases (same meaning)

- `extension_hours_shift` / `extention_hours_shift` → treated as **minutes** (same as `extension_minutes`)
- Overtime flags may also be nested:

```json
{
  "overtime_rules": {
    "is_overtime_before_early_clock_in": false,
    "is_overtime_after_extension_hours_shift": false,
    "is_after_finish_working_hours": false
  }
}
```

Flat flags override nested ones when both are sent.

---

## GET response shape

```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Constraint rules retrieved successfully",
  "payload": {
    "constraint_id": "d2caa1b5-9103-445d-86ef-0826f885b533",
    "early_clock_in_minutes": 30,
    "can_clock_in_before": 120,
    "extension_minutes": 120,
    "extension_hours_shift": 120,
    "max_over_time": 0,
    "out_zone_minutes": 20,
    "is_overtime_before_early_clock_in": false,
    "is_overtime_after_extension_hours_shift": false,
    "is_after_finish_working_hours": false,
    "overtime_rules": {
      "is_overtime_before_early_clock_in": false,
      "is_overtime_after_extension_hours_shift": false,
      "is_after_finish_working_hours": false
    },
    "working_hours": 9,
    "max_working_hours": 9,
    "out_zone_rules": { "duration_minutes": 20 }
  }
}
```

Notes:
- `extension_minutes` and `extension_hours_shift` are the **same minutes value**.
- Use `extension_minutes` in new UI code; keep reading the alias only for older builds.
- `max_over_time` on GET/PATCH is minutes (backend still converts for internal calculators).

---

## Migration checklist for FE

1. **Stop treating `extension_hours_shift` as hours.**  
   Old: `2` meant 2 hours.  
   New: send `120` for a 2-hour extension (or use `extension_minutes: 120`).

2. **Stop treating `max_over_time` as hours on this rules API.**  
   Old: `2` meant 2 hours.  
   New: send `120` for 2 hours of overtime cap.

3. **Form labels / inputs**  
   - Early clock-in → minutes  
   - Can clock in before → minutes  
   - Extension → minutes (`extension_minutes`)  
   - Max overtime → minutes  
   - Out of zone → minutes  

4. **Convert UI “hours” pickers if you still show hours to users**

```ts
const toMinutes = (hours: number) => Math.round(hours * 60);
const toHours = (minutes: number) => minutes / 60;

// submit
extension_minutes: toMinutes(extensionHoursFromUi),
max_over_time: toMinutes(maxOvertimeHoursFromUi),
```

5. **Default example matching product rules**

| Setting | Minutes to send |
|---|---|
| Early clock-in 30 min | `early_clock_in_minutes: 30` |
| Deadline 2 hours after start | `can_clock_in_before: 120` |
| Extension 2 hours | `extension_minutes: 120` |
| No overtime cap | `max_over_time: 0` |
| Out zone 20 min | `out_zone_minutes: 20` |

6. **Overtime toggles**  
   Send the three booleans flat (recommended). They only open outer overtime zones; they are not durations.

---

## What did not change

- Shift period `start_time` / `end_time` on weekly schedule still use `HH:mm`.
- `working_hours` / `max_working_hours` remain in **hours**.
- Clock-in / calendar / user-constraint behaviour still uses the same rules; only the **rules API units** for extension + max overtime changed to minutes.

---

## Quick TypeScript types

```ts
type ConstraintRulesPayload = {
  can_clock_in_before?: number | null;       // minutes
  early_clock_in_minutes?: number | null;    // minutes
  extension_minutes?: number | null;         // minutes (preferred)
  extension_hours_shift?: number | null;     // minutes alias (legacy key name)
  max_over_time?: number | null;             // minutes
  out_zone_minutes?: number | null;          // minutes
  working_hours?: number | null;             // hours
  max_working_hours?: number | null;         // hours
  is_overtime_before_early_clock_in?: boolean;
  is_overtime_after_extension_hours_shift?: boolean;
  is_after_finish_working_hours?: boolean;
  overtime_rules?: {
    is_overtime_before_early_clock_in?: boolean;
    is_overtime_after_extension_hours_shift?: boolean;
    is_after_finish_working_hours?: boolean;
  };
};
```
