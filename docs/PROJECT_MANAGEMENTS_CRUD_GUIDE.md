# Project Managements CRUD API Guide

## Overview

The **Project Managements** module provides CRUD operations for managing project management entries. Each entry has a `name` and an optional `project_id` linking it to a project.

## Database Table

**Table:** `project_managements`

| Column       | Type      | Nullable | Description                        |
|--------------|-----------|----------|------------------------------------|
| `id`         | bigint    | No       | Primary key (auto-increment)       |
| `project_id` | uuid      | Yes      | Foreign key to `projects` table    |
| `name`       | string    | No       | Name of the project management     |
| `created_at` | timestamp | Yes      | Creation timestamp                 |
| `updated_at` | timestamp | Yes      | Last update timestamp              |

## Response Structure

```json
{
  "id": 1,
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "Project Management A",
  "created_at": "2026-07-18 15:00:00",
  "updated_at": "2026-07-18 15:00:00"
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

### 1. List All Project Managements

```http
GET /api/v1/project-managements
```

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
      "name": "Project Management A",
      "created_at": "2026-07-18 15:00:00",
      "updated_at": "2026-07-18 15:00:00"
    },
    {
      "id": 2,
      "project_id": null,
      "name": "Project Management B",
      "created_at": "2026-07-18 15:30:00",
      "updated_at": "2026-07-18 15:30:00"
    }
  ]
}
```

---

### 2. Create Project Management

```http
POST /api/v1/project-managements
```

**Request Body:**
```json
{
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "Project Management A"
}
```

| Field         | Type   | Required | Validation                        |
|---------------|--------|----------|-----------------------------------|
| `project_id`  | string | No       | Must exist in `projects` table    |
| `name`        | string | Yes      | Max 255 characters                |

**Response:**
```json
{
  "data": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "Project Management A",
    "created_at": "2026-07-18 15:00:00",
    "updated_at": "2026-07-18 15:00:00"
  }
}
```

---

### 3. Get Single Project Management

```http
GET /api/v1/project-managements/{id}
```

**URL Parameter:**
- `id` — The project management ID (integer)

**Response:**
```json
{
  "data": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "Project Management A",
    "created_at": "2026-07-18 15:00:00",
    "updated_at": "2026-07-18 15:00:00"
  }
}
```

---

### 4. Update Project Management

```http
PUT /api/v1/project-managements/{id}
```

**URL Parameter:**
- `id` — The project management ID (integer)

**Request Body (all fields optional — only sent fields are updated):**
```json
{
  "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
  "name": "Updated Name"
}
```

| Field         | Type   | Required | Validation                        |
|---------------|--------|----------|-----------------------------------|
| `project_id`  | string | No       | Must exist in `projects` table    |
| `name`        | string | No*      | Max 255 characters (*if provided) |

**Response:**
```json
{
  "data": {
    "id": 1,
    "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
    "name": "Updated Name",
    "created_at": "2026-07-18 15:00:00",
    "updated_at": "2026-07-18 15:05:00"
  }
}
```

---

### 5. Delete Project Management

```http
DELETE /api/v1/project-managements/{id}
```

**URL Parameter:**
- `id` — The project management ID (integer)

**Response:**
```json
{
  "message": "Deleted successfully"
}
```

---

## Integration with Order Permits

The `project_managements` table is linked to `project_order_permit` via the `project_management_id` foreign key. When creating or updating an order permit under a project, you can pass `project_management_id`:

### Create Order Permit with Project Management

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
      "name": "Work Order 1",
      "type": "Type A",
      "assigned_date": "2026-07-18"
    }
  ]
}
```

### Order Permit Response (with project_management fields)

```json
{
  "data": [
    {
      "id": 1,
      "project_id": "606e9811-0983-4a62-8128-1590fb73a397",
      "project_management_id": 1,
      "project_management_name": "Project Management A",
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
      "created_at": "2026-07-18 15:00:00",
      "updated_at": "2026-07-18 15:00:00"
    }
  ]
}
```

---

## Frontend Implementation Notes

- **List view**: Call `GET /project-managements` to populate dropdowns or tables
- **Create form**: `project_id` is optional — use a project selector if needed
- **Update form**: Use `PUT` with only the fields you want to change
- **Delete**: Call `DELETE /project-managements/{id}` — no body required
- **Order Permit integration**: When creating work orders, pass `project_management_id` from the dropdown populated by the list endpoint
