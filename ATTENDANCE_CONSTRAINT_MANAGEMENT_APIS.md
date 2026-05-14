# Attendance Constraint Management APIs

> **Added:** 2026-05-14  
> **Status:** Production-safe — no breaking changes to existing APIs  
> **Base URL:** `api/v1/attendance/constraints`

---

## Table of Contents

1. [Overview](#overview)
2. [Authentication](#authentication)
3. [New Database Table](#new-database-table)
4. [API Endpoints](#api-endpoints)
   - [Update Constraint Basic Info](#1-update-constraint-basic-info)
   - [Get Constraint Employees](#2-get-constraint-employees)
   - [Assign Employee to Main Constraint](#3-assign-employee-to-main-constraint)
   - [Create Additional Locations](#4-create-additional-locations)
   - [Get Constraint Locations](#5-get-constraint-locations)
   - [Update Location](#6-update-location)
   - [Delete Location](#7-delete-location)
   - [Get Day Shifts](#8-get-day-shifts)
5. [How Additional Locations Work](#how-additional-locations-work)
6. [Postman Collection](#postman-collection)
7. [Migration Instructions](#migration-instructions)

---

## Overview

These APIs extend the existing Attendance Constraint module with granular management capabilities:

| Feature | Endpoint | Method |
|---|---|---|
| Update basic info (name, type, branches) | `PATCH /{constraint}/basic-info` | PATCH |
| List employees in a constraint | `GET /{constraint}/employees` | GET |
| Assign employee to main constraint | `POST /{constraint}/employees` | POST |
| Create additional locations (bulk) | `POST /{constraint}/locations` | POST |
| List locations for a constraint | `GET /{constraint}/locations` | GET |
| Update a location | `PUT /locations/{location}` | PUT |
| Delete a location | `DELETE /locations/{location}` | DELETE |
| Get day shifts (weekly schedule) | `GET /{constraint}/day-shifts` | GET |

All endpoints are under the `api/v1/attendance/constraints` prefix and require `auth:api` middleware.

---

## Authentication

All endpoints require a valid Bearer token in the `Authorization` header:

```
Authorization: Bearer {token}
```

Permissions follow the existing attendance constraint permission scheme:
- **View** endpoints: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`
- **Create/Update** endpoints: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`
- **Delete** endpoints: `EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE`

---

## New Database Table

### `attendance_constraint_locations`

A new table to store additional GPS locations per constraint. This does **not** modify the existing `branch_locations` JSON column.

| Column | Type | Description |
|---|---|---|
| `id` | UUID (PK) | Auto-generated UUID |
| `attendance_constraint_id` | UUID (FK) | References `attendance_constraints.id` (cascade delete) |
| `company_id` | UUID | Multi-tenant company identifier |
| `name` | VARCHAR, nullable | Display name for the location |
| `latitude` | DECIMAL(10,7) | GPS latitude |
| `longitude` | DECIMAL(10,7) | GPS longitude |
| `radius` | INT, default 100 | Geofence radius in meters |
| `created_by` | UUID, nullable | User who created the location |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

**Indexes:**
- `acl_constraint_id_index` on `attendance_constraint_id`
- `acl_company_id_index` on `company_id`

**Migration file:** `modules/Attendance/Database/migrations/2026_05_14_000001_create_attendance_constraint_locations_table.php`

---

## API Endpoints

### 1. Update Constraint Basic Info

Update only the basic information of a constraint without touching the full constraint config.

```
PATCH /api/v1/attendance/constraints/{constraint_id}/basic-info
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`

**Request Body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `constraint_name` | string | No | New name for the constraint |
| `constraint_type` | string | No | Must be a valid type (e.g. `regular`) |
| `branch_ids` | array of UUIDs | No | Management hierarchy IDs for branches |

All fields are optional — send only what you want to update.

**Example Request:**
```json
{
    "constraint_name": "Eastern Region Constraint",
    "constraint_type": "regular",
    "branch_ids": ["branch-uuid-1", "branch-uuid-2"]
}
```

**Example Response (200):**
```json
{
    "data": {
        "id": "constraint-uuid",
        "constraint_name": "Eastern Region Constraint",
        "constraint_type": "منتظم",
        "constraint_code": "regular",
        "branch_locations": [],
        "is_active": 1,
        "priority": 1,
        "config": {},
        "branches": [
            {"id": "branch-uuid-1", "name": "Branch 1"}
        ],
        "created_by": "Admin User",
        "created_at": "2026-05-14 10:00:00"
    },
    "message": "Constraint basic info updated successfully"
}
```

---

### 2. Get Constraint Employees

Retrieve all employees assigned to a specific attendance constraint.

```
GET /api/v1/attendance/constraints/{constraint_id}/employees
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`

**Employee Sources:**
- **`main`** — Employee's `user_professional_datas.attendance_constraint_id` equals this constraint
- **`additional`** — Employee is linked via the `attendance_constraint_user` pivot table

**Example Response (200):**
```json
{
    "data": [
        {
            "id": "user-uuid-1",
            "name": "Mohamed Mashal",
            "email": "mohamed@gmail.com",
            "phone": "06454664562",
            "source": "main"
        },
        {
            "id": "user-uuid-2",
            "name": "Ahmed Ali",
            "email": "ahmed@gmail.com",
            "phone": "0512345678",
            "source": "additional"
        }
    ],
    "message": "Constraint employees retrieved successfully"
}
```

---

### 3. Assign Employee to Main Constraint

Assign an employee to this constraint as their **primary** attendance constraint. This updates `user_professional_datas.attendance_constraint_id`.

```
POST /api/v1/attendance/constraints/{constraint_id}/employees
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`

**Request Body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `user_id` | UUID | Yes | The user to assign |

**Example Request:**
```json
{
    "user_id": "user-uuid-here"
}
```

**Example Response (200):**
```json
{
    "data": {
        "user_id": "user-uuid",
        "constraint_id": "constraint-uuid"
    },
    "message": "Employee assigned to constraint successfully"
}
```

**Error Response (404):**
```json
{
    "message": "User professional data not found"
}
```

**Important:** This sets the **main** constraint for the user, not an additional/secondary constraint. For additional constraints, use the existing `POST /users/{userId}/additional` endpoint.

---

### 4. Create Additional Locations

Create one or more additional GPS locations for a constraint. Supports bulk creation.

```
POST /api/v1/attendance/constraints/{constraint_id}/locations
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`

**Request Body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `locations` | array | Yes | Array of location objects (min 1) |
| `locations.*.name` | string | No | Display name |
| `locations.*.latitude` | numeric | Yes | GPS latitude (-90 to 90) |
| `locations.*.longitude` | numeric | Yes | GPS longitude (-180 to 180) |
| `locations.*.radius` | integer | Yes | Geofence radius in meters (1-10000) |

**Example Request:**
```json
{
    "locations": [
        {
            "name": "Riyadh Office",
            "latitude": 24.7136,
            "longitude": 46.6753,
            "radius": 200
        },
        {
            "name": "Jeddah Branch",
            "latitude": 21.4858,
            "longitude": 39.1925,
            "radius": 150
        }
    ]
}
```

**Example Response (200):**
```json
{
    "data": [
        {
            "id": "loc-uuid-1",
            "attendance_constraint_id": "constraint-uuid",
            "company_id": "company-uuid",
            "name": "Riyadh Office",
            "latitude": 24.7136,
            "longitude": 46.6753,
            "radius": 200,
            "created_by": "admin-uuid",
            "created_at": "2026-05-14T10:00:00.000000Z",
            "updated_at": "2026-05-14T10:00:00.000000Z"
        }
    ],
    "message": "Locations created successfully"
}
```

---

### 5. Get Constraint Locations

Get all additional locations defined for a specific constraint.

```
GET /api/v1/attendance/constraints/{constraint_id}/locations
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`

**Example Response (200):**
```json
{
    "data": [
        {
            "id": "loc-uuid-1",
            "name": "Riyadh Office",
            "latitude": 24.7136,
            "longitude": 46.6753,
            "radius": 200,
            "created_at": "2026-05-14 10:00:00"
        },
        {
            "id": "loc-uuid-2",
            "name": "Jeddah Branch",
            "latitude": 21.4858,
            "longitude": 39.1925,
            "radius": 150,
            "created_at": "2026-05-14 10:00:00"
        }
    ],
    "message": "Constraint locations retrieved successfully"
}
```

---

### 6. Update Location

Update a specific location's GPS coordinates or radius.

```
PUT /api/v1/attendance/constraints/locations/{location_id}
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_UPDATE`

**Request Body:**

| Field | Type | Required | Description |
|---|---|---|---|
| `name` | string | No | Updated display name |
| `latitude` | numeric | No | GPS latitude (-90 to 90) |
| `longitude` | numeric | No | GPS longitude (-180 to 180) |
| `radius` | integer | No | Geofence radius in meters (1-10000) |

All fields are optional — send only what you want to update.

**Example Request:**
```json
{
    "latitude": 24.7200,
    "longitude": 46.6800,
    "radius": 250
}
```

**Example Response (200):**
```json
{
    "data": {
        "id": "loc-uuid-1",
        "name": "Riyadh Office",
        "latitude": 24.72,
        "longitude": 46.68,
        "radius": 250
    },
    "message": "Location updated successfully"
}
```

---

### 7. Delete Location

Permanently delete a specific location.

```
DELETE /api/v1/attendance/constraints/locations/{location_id}
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_DELETE`

**Example Response (200):**
```json
{
    "message": "Location deleted successfully"
}
```

---

### 8. Get Day Shifts

Get the full weekly schedule (day shifts) for a specific constraint. Returns all 7 days with their work periods, lateness rules, and early clock-in rules.

```
GET /api/v1/attendance/constraints/{constraint_id}/day-shifts
```

**Permission:** `EMPLOYEE_ATTENDANCE_CONSTRAINTS_VIEW`

**Example Response (200):**
```json
{
    "data": {
        "constraint_id": "constraint-uuid",
        "constraint_name": "Eastern Region Constraint",
        "max_over_time": 4,
        "shifts": [
            {
                "day": "saturday",
                "enabled": false,
                "periods": [],
                "lateness_rules": null,
                "early_clock_in_rules": null
            },
            {
                "day": "sunday",
                "enabled": true,
                "periods": [
                    {
                        "start_time": "08:00",
                        "end_time": "12:00",
                        "extends_to_next_day": false
                    },
                    {
                        "start_time": "13:00",
                        "end_time": "17:00",
                        "extends_to_next_day": false
                    }
                ],
                "lateness_rules": {
                    "lateness_period": 15,
                    "lateness_unit": "minute"
                },
                "early_clock_in_rules": {
                    "allowed_minutes_before": 30
                }
            },
            {
                "day": "monday",
                "enabled": true,
                "periods": [
                    {
                        "start_time": "08:00",
                        "end_time": "17:00",
                        "extends_to_next_day": false
                    }
                ],
                "lateness_rules": null,
                "early_clock_in_rules": null
            },
            {
                "day": "tuesday",
                "enabled": true,
                "periods": [
                    {
                        "start_time": "08:00",
                        "end_time": "17:00",
                        "extends_to_next_day": false
                    }
                ],
                "lateness_rules": null,
                "early_clock_in_rules": null
            },
            {
                "day": "wednesday",
                "enabled": true,
                "periods": [
                    {
                        "start_time": "08:00",
                        "end_time": "17:00",
                        "extends_to_next_day": false
                    }
                ],
                "lateness_rules": null,
                "early_clock_in_rules": null
            },
            {
                "day": "thursday",
                "enabled": true,
                "periods": [
                    {
                        "start_time": "08:00",
                        "end_time": "14:00",
                        "extends_to_next_day": false
                    }
                ],
                "lateness_rules": null,
                "early_clock_in_rules": null
            },
            {
                "day": "friday",
                "enabled": false,
                "periods": [],
                "lateness_rules": null,
                "early_clock_in_rules": null
            }
        ]
    },
    "message": "Day shifts retrieved successfully"
}
```

**Response Fields:**

| Field | Description |
|---|---|
| `day` | Day name (saturday through friday) |
| `enabled` | Whether this day is a working day |
| `periods` | Array of work periods with `start_time`, `end_time`, `extends_to_next_day` |
| `lateness_rules` | Per-day lateness grace period configuration (null if not set) |
| `early_clock_in_rules` | Per-day early clock-in configuration (null if not set) |

---

## How Additional Locations Work

The `additional_locations` field in the existing `GET /api/v1/attendance/constraints/user` (user-constraint/today) API response now pulls from **two sources**:

1. **`branch_locations` JSON** from each additional constraint (existing behavior, unchanged)
2. **`attendance_constraint_locations` table rows** from each additional constraint (new)

Both sources are merged and returned in the `additional_locations` array. This means:

- Locations created via the new `POST /{constraint}/locations` API will automatically appear in the `additional_locations` for any user who has this constraint assigned as an additional constraint (via the `attendance_constraint_user` pivot table).
- The existing `branch_locations` JSON column on the constraint remains untouched and continues to work as before.
- Location validation at clock-in (`mergeAdditionalLocationsForUser`) also includes both sources, so employees can clock in from any of these locations.

### Data Flow Diagram

```
attendance_constraints
    ├── branch_locations (JSON)           ──┐
    │                                       ├──▶ additional_locations in user-constraint/today
    └── attendance_constraint_locations     ──┘
         (new table, CRUD via API)
```

---

## Postman Collection

A ready-to-import Postman collection is available at the project root:

```
Attendance_Constraint_Management_APIs.postman_collection.json
```

**How to import:**
1. Open Postman
2. Click **Import** → **Upload Files**
3. Select `Attendance_Constraint_Management_APIs.postman_collection.json`
4. Update the collection variables:
   - `base_url` — Your API server URL (default: `http://localhost:8000`)
   - `token` — Your Bearer authentication token
   - `constraint_id` — A valid constraint UUID
   - `location_id` — A valid location UUID
   - `user_id` — A valid user UUID

---

## Migration Instructions

Run the migration to create the new `attendance_constraint_locations` table:

```bash
php artisan migrate --path=modules/Attendance/Database/migrations/2026_05_14_000001_create_attendance_constraint_locations_table.php
```

Or run all pending migrations:

```bash
php artisan migrate
```

No data migration is needed — this is purely additive.

---

## Files Changed

| File | Change Type | Description |
|---|---|---|
| `modules/Attendance/Database/migrations/2026_05_14_000001_...` | **New** | Migration for `attendance_constraint_locations` table |
| `modules/Attendance/Models/AttendanceConstraintLocation.php` | **New** | Eloquent model for additional locations |
| `modules/Attendance/Models/AttendanceConstraint.php` | Modified | Added `additionalLocations()` hasMany relationship |
| `modules/Attendance/Controllers/AttendanceConstraintController.php` | Modified | Added 8 new endpoint methods |
| `modules/Attendance/Routes/attendance_constraints.php` | Modified | Added 8 new routes |
| `modules/Attendance/Services/AttendanceConstraintService.php` | Modified | Updated `buildAdditionalLocationRules` and `mergeAdditionalLocationsForUser` to include new table locations |
| `Attendance_Constraint_Management_APIs.postman_collection.json` | **New** | Postman collection |
| `ATTENDANCE_CONSTRAINT_MANAGEMENT_APIS.md` | **New** | This documentation |
