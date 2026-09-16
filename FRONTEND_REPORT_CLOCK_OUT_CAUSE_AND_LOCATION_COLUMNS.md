# Report create — clock-out cause & punch location columns

> **Who this is for:** web frontend creating a report via `POST /api/v1/reports`.  
> **What changed:** the attendance-detail column picker now has three extra IDs. Checking them prints **سبب الانصراف**, **موقع الدخول**, and **موقع الخروج** on the PDF (both “by day” and “employee per page”).

The JSON field is still `config.step3.attendanceDataTypeIds` even if the wizard screen is labelled **step 4** in the UI. Salary remains `config.step4`.

---

## TL;DR

1. Load checkboxes from `GET /api/v1/reports/lookups` → `payload.attendance_detail_columns`.
2. On create, send the checked IDs as `config.step3.attendanceDataTypeIds`.
3. `[]` / omitted still means **all columns**, including the three new ones.
4. An existing explicit list (without the new IDs) does **not** show the new columns until the user checks them.

---

## 1. New column IDs

| `id` | Arabic | English | Source |
|---|---|---|---|
| `clock_out_cause` | سبب الانصراف | Clock-out cause | `attendances.shift_end_method` (falls back to `manual` when the employee clocked out and the method was never written) |
| `clock_in_location` | موقع الدخول | Clock-in location | `attendances.clock_in_location` |
| `clock_out_location` | موقع الخروج | Clock-out location | `attendances.clock_out_location` |

Place them in the picker **after** `actual_out` (خروج فعلي) and **before** `delay` (تأخير). That matches lookup order.

Do **not** mix these with `payload.attendance_data_types` (`attendance_days`, `delays`, …). Those are attendance **filters**, not table columns.

---

## 2. Lookups

```
GET /api/v1/reports/lookups
```

Same headers as every other API call (`Authorization`, `x-domain`, `Accept: application/json`).

`payload.attendance_detail_columns` is `{id, label: {ar, en}}[]`. Render `label.ar` / `label.en` from the report language (or the UI locale). Example of the new rows:

```json
{
  "id": "clock_out_cause",
  "label": { "ar": "سبب الانصراف", "en": "Clock-out cause" }
}
```

```json
{
  "id": "clock_in_location",
  "label": { "ar": "موقع الدخول", "en": "Clock-in location" }
}
```

```json
{
  "id": "clock_out_location",
  "label": { "ar": "موقع الخروج", "en": "Clock-out location" }
}
```

Full legal ID set (send any subset):

```
day, branch, management, official_in, official_out, actual_in, actual_out,
clock_out_cause, clock_in_location, clock_out_location,
delay, overtime, total_hours, calculated_hours
```

An unknown ID on create returns **422**.

---

## 3. Create payload

```
POST /api/v1/reports
```

```json
{
  "config": {
    "step3": {
      "attendanceDataTypeIds": [
        "day",
        "actual_in",
        "actual_out",
        "clock_out_cause",
        "clock_in_location",
        "clock_out_location",
        "delay",
        "total_hours"
      ]
    }
  }
}
```

Keep the rest of `step3` as you already send it (`display_mode`, `attendancePattern`, …).

### Visibility rules

| `attendanceDataTypeIds` | PDF columns |
|---|---|
| omitted or `[]` | every column in the lookup list (including the three new ones) |
| explicit list | only the IDs in that list |
| saved report whose list was created before this change | unchanged — new columns stay off until the user adds the IDs |

---

## 4. What the PDF prints

### Clock-out cause

Empty while the shift is still open (no clock-out). After clock-out:

| Stored `shift_end_method` | Arabic | English |
|---|---|---|
| *(empty / null)* | الموظف | Employee |
| `manual` | الموظف | Employee |
| `auto_max_ot` | تلقائي — نهاية الوردية | Auto — shift end |
| `auto_next_shift` | تلقائي — الوردية التالية | Auto — next shift |
| `auto_out_zone` | تلقائي — خارج النطاق | Auto — out of zone |
| `auto_no_location` | تلقائي — بدون موقع | Auto — no GPS |
| `auto_radius_enforcement` / `auto_radius` | تلقائي — نصف القطر | Auto — radius |
| `auto_time_limit` | تلقائي — حد الوقت | Auto — time limit |

The frontend does **not** need to translate these for the PDF. Use the table only if you show a preview or a tooltip next to the checkbox.

### Locations

Each cell is a short text string, in this order of preference:

1. `address` (or `formatted_address`)
2. `name`
3. `latitude, longitude` (5 decimal places)

Missing GPS prints `-`. Several clock-in/out pairs on the same day still become sub-rows; cause and locations follow the same session as `actual_in` / `actual_out`.

Excel/CSV summaries are unchanged (they never had per-session columns).

---

## 5. `__daily` session shape (optional)

If you inspect extracted report data, each `attendance_sessions[]` item is now:

```json
{
  "clock_in_time": "2026-09-15 08:02:00",
  "clock_out_time": "2026-09-15 17:00:00",
  "clock_out_cause": "manual",
  "clock_in_location_label": "King Fahd Rd",
  "clock_out_location_label": "24.71360, 46.67530"
}
```

`clock_out_cause` is the **code** (`manual`, `auto_max_ot`, …). The PDF maps it to Arabic/English from `step1.reportLanguage`.
