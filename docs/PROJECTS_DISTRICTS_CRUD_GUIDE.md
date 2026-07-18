# Projects Districts CRUD API Guide

## Overview

The **Projects Districts** module provides CRUD operations for managing project district entries. Each entry has a `name` and an optional `project_id` linking it to a project.

## Database Table

**Table:** `projects_districts`

| Column       | Type      | Nullable | Description                        |
|--------------|-----------|----------|------------------------------------|
| `id`         | bigint    | No       | Primary key (auto-increment)       |
| `project_id` | uuid      | Yes      | Foreign key to `projects` table    |
| `name`       | string    | No       | Name of the project district       |
| `created_at` | timestamp | Yes      | Creation timestamp                 |
| `updated_at` | timestamp | Yes      | Last update timestamp              |

## Response Structure

```json
{
  "id": 1,
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "District A",
  "created_at": "2026-07-18 16:00:00",
  "updated_at": "2026-07-18 16:00:00"
}
```

## Required Headers

All requests require:

```
Authorization: Bearer {jwt_token}
Content-Type: application/json
X-Tenant-ID: {tenant_id}
```

---

## API Endpoints

Base URL: `https://core-be-production.constrix-nv.com/api/v1`

### 1. List All Project Districts

```http
GET /api/v1/projects-districts
```

**Response:**
```json
{
  "payload": [
    {
      "id": 1,
      "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
      "name": "District A",
      "created_at": "2026-07-18 16:00:00",
      "updated_at": "2026-07-18 16:00:00"
    },
    {
      "id": 2,
      "project_id": null,
      "name": "District B",
      "created_at": "2026-07-18 16:30:00",
      "updated_at": "2026-07-18 16:30:00"
    }
  ]
}
```

---

### 2. Create Project District

```http
POST /api/v1/projects-districts
```

**Request Body:**
```json
{
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "District A"
}
```

| Field         | Type   | Required | Validation                        |
|---------------|--------|----------|-----------------------------------|
| `project_id`  | string | No       | Must exist in `projects` table    |
| `name`        | string | Yes      | Max 255 characters                |

**Response:**
```json
{
  "payload": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "District A",
    "created_at": "2026-07-18 16:00:00",
    "updated_at": "2026-07-18 16:00:00"
  }
}
```

---

### 3. Get Single Project District

```http
GET /api/v1/projects-districts/{id}
```

**URL Parameter:**
- `id` — The project district ID (integer)

**Response:**
```json
{
  "payload": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "District A",
    "created_at": "2026-07-18 16:00:00",
    "updated_at": "2026-07-18 16:00:00"
  }
}
```

---

### 4. Update Project District

```http
PUT /api/v1/projects-districts/{id}
```

**URL Parameter:**
- `id` — The project district ID (integer)

**Request Body (all fields optional — only sent fields are updated):**
```json
{
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "Updated District Name"
}
```

| Field         | Type   | Required | Validation                        |
|---------------|--------|----------|-----------------------------------|
| `project_id`  | string | No       | Must exist in `projects` table    |
| `name`        | string | No*      | Max 255 characters (*if provided) |

**Response:**
```json
{
  "payload": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "Updated District Name",
    "created_at": "2026-07-18 16:00:00",
    "updated_at": "2026-07-18 16:05:00"
  }
}
```

---

### 5. Delete Project District

```http
DELETE /api/v1/projects-districts/{id}
```

**URL Parameter:**
- `id` — The project district ID (integer)

**Response:** `204 No Content`

---

## Integration with Order Permits

The `projects_districts` table is linked to `project_order_permit` via the `projects_district_id` foreign key. When creating or updating an order permit under a project, you can pass `projects_district_id`:

### Create Order Permit with Project District

```http
POST /api/v1/projects/{projectId}/order-permits
```

**Request Body:**
```json
{
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "work_orders": [
    {
      "project_management_id": 1,
      "projects_district_id": 2,
      "name": "Work Order 1",
      "type": "Type A",
      "assigned_date": "2026-07-18"
    }
  ]
}
```

| Field                   | Type    | Required | Validation                              |
|-------------------------|---------|----------|-----------------------------------------|
| `projects_district_id`  | integer | No       | Must exist in `projects_districts` table |

### Update Order Permit with Project District

```http
PUT /api/v1/projects/{projectId}/order-permits/{id}
```

**Request Body:**
```json
{
  "projects_district_id": 2
}
```

### Order Permit Response (with project district fields)

```json
{
  "payload": [
    {
      "id": 1,
      "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
      "project_management_id": 1,
      "project_management_name": "Project Management A",
      "projects_district_id": 2,
      "projects_district_name": "District A",
      "order_permit_id": null,
      "order_permit_department_id": null,
      "contractor_id": null,
      "contractor_name": null,
      "name": "Work Order 1",
      "type": "Type A",
      "assigned_date": "2026-07-18",
      "state_id": null,
      "state_name": null,
      "lat": null,
      "long": null,
      "price": null,
      "created_at": "2026-07-18 16:00:00",
      "updated_at": "2026-07-18 16:00:00"
    }
  ]
}
```

---

## Frontend Implementation Notes

- **List view**: Call `GET /projects-districts` to populate dropdowns or tables
- **Create form**: `project_id` is optional — use a project selector if needed
- **Update form**: Use `PUT` with only the fields you want to change
- **Delete**: Call `DELETE /projects-districts/{id}` — no body required, returns 204
- **Order Permit integration**: When creating work orders, pass `projects_district_id` from the dropdown populated by the list endpoint
- **Combined with Project Management**: Both `project_management_id` and `projects_district_id` can be passed together when creating/updating order permits
