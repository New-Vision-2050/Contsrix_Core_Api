# Project List API — Filters & Cascading Dependencies Guide

## Endpoint

```
GET /api/v1/projects?page=1&per_page=10
```

### Required Headers

| Header | Value |
|--------|-------|
| `Authorization` | `Bearer {token}` |
| `X-Tenant` | `{tenant_id}` |

---

## Available Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `page` | integer | No | Page number (default: `1`) |
| `per_page` | integer | No | Items per page (default: `10`) |
| `name` | string | No | Project name — partial match (LIKE `%value%`) |
| `project_type_id` | integer | No | Root project type ID — exact match |
| `sub_project_type_id` | integer | No | Sub project type ID — exact match |
| `sub_sub_project_type_id` | integer | No | Sub-sub project type ID — exact match |
| `manager_id` | UUID | No | Project manager user ID — exact match |
| `branch_id` | UUID | No | Branch ID — exact match |
| `project_owner_type` | string | No | Owner type: `company` or `individual` — exact match |
| `project_owner_id` | UUID | No | Owner ID (company UUID or user UUID) — exact match |
| `contract_id` | UUID | No | Contract ID — exact match |
| `client_id` | UUID | No | Client user ID — exact match |
| `management_id` | UUID | No | Management hierarchy ID — exact match |
| `status` | integer | No | Project status: `-1`, `0`, or `1` — exact match |

---

## Cascading Filter Dependencies

The frontend must respect the following dependency chains. A dependent filter should only be enabled/populated **after** its parent filter has a value selected.

### 1. Project Type Hierarchy (3 levels)

```
project_type_id  →  sub_project_type_id  →  sub_sub_project_type_id
```

**Behavior:**

1. **Step 1 — Load root project types:**
   - Call `GET /api/v1/project-types/roots`
   - Populate the `project_type_id` dropdown

2. **Step 2 — After selecting a `project_type_id`, load sub types:**
   - Call `GET /api/v1/project-types/{project_type_id}/children`
   - Populate the `sub_project_type_id` dropdown
   - **Clear** any previously selected `sub_project_type_id` and `sub_sub_project_type_id`

3. **Step 3 — After selecting a `sub_project_type_id`, load sub-sub types:**
   - Call `GET /api/v1/project-types/{sub_project_type_id}/children`
   - Populate the `sub_sub_project_type_id` dropdown
   - **Clear** any previously selected `sub_sub_project_type_id`

**Alternative:** Use `GET /api/v1/project-types/filter?parent_id={id}` to get children of a specific parent.

**UI Rules:**
- `sub_project_type_id` dropdown is **disabled** until `project_type_id` is selected
- `sub_sub_project_type_id` dropdown is **disabled** until `sub_project_type_id` is selected
- When a parent changes, reset all child dropdowns to empty

---

### 2. Project Owner (2 levels)

```
project_owner_type  →  project_owner_id
```

**Behavior:**

1. **Step 1 — Select owner type:**
   - User selects from a fixed dropdown:
     - `company` → "Company"
     - `individual` → "Individual"

2. **Step 2 — After selecting `project_owner_type`, load owner options:**
   - If `project_owner_type` = `company`:
     - Call `GET /api/v1/companies` (paginated list of companies)
     - Populate `project_owner_id` dropdown with company UUIDs and names
   - If `project_owner_type` = `individual`:
     - Call `GET /api/v1/users` (paginated list of users)
     - Populate `project_owner_id` dropdown with user UUIDs and names

3. **Step 3 — After selecting `project_owner_id`:**
   - Both `project_owner_type` and `project_owner_id` are sent as query params

**UI Rules:**
- `project_owner_id` dropdown is **disabled** until `project_owner_type` is selected
- When `project_owner_type` changes, **clear** `project_owner_id`

---

### 3. Project Manager (standalone)

```
manager_id  (no dependency)
```

**Behavior:**
- Call `GET /api/v1/users` to load the list of users
- Populate `manager_id` dropdown with user UUIDs and names
- This filter has no dependencies — it can be selected independently

---

## Example API Calls

### Filter by project type hierarchy + manager + owner

```
GET /api/v1/projects?page=1&per_page=10
    &project_type_id=5
    &sub_project_type_id=12
    &sub_sub_project_type_id=25
    &manager_id=550e8400-e29b-41d4-a716-446655440000
    &project_owner_type=company
    &project_owner_id=660e8400-e29b-41d4-a716-446655440000
```

### Filter by owner type = individual + status

```
GET /api/v1/projects?page=1&per_page=10
    &project_owner_type=individual
    &project_owner_id=770e8400-e29b-41d4-a716-446655440000
    &status=1
```

### Filter by name only

```
GET /api/v1/projects?page=1&per_page=10
    &name=Tower
```

---

## Response Structure

```json
{
  "data": [
    {
      "id": "uuid",
      "name": "Project Name",
      "project_type_id": 5,
      "sub_project_type_id": 12,
      "sub_sub_project_type_id": 25,
      "manager_id": "uuid",
      "project_owner_type": "company",
      "project_owner_id": "uuid",
      "status": 1,
      "projectType": { "id": 5, "name": "..." },
      "subProjectType": { "id": 12, "name": "..." },
      "subSubProjectType": { "id": 25, "name": "..." },
      "manager": { "id": "uuid", "name": "..." },
      "ownerCompany": { "id": "uuid", "name": "..." },
      "ownerIndividual": null
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 10,
    "total": 50,
    "from": 1,
    "to": 10
  }
}
```

---

## Frontend Implementation Checklist

- [ ] `project_type_id` dropdown loads from `GET /api/v1/project-types/roots`
- [ ] `sub_project_type_id` dropdown loads from `GET /api/v1/project-types/{id}/children` after parent is selected
- [ ] `sub_sub_project_type_id` dropdown loads from `GET /api/v1/project-types/{id}/children` after sub type is selected
- [ ] Changing a parent dropdown clears all child dropdowns
- [ ] `project_owner_type` is a fixed select with `company` / `individual` options
- [ ] `project_owner_id` dropdown loads from `GET /api/v1/companies` or `GET /api/v1/users` based on owner type
- [ ] Changing `project_owner_type` clears `project_owner_id`
- [ ] `manager_id` dropdown loads from `GET /api/v1/users`
- [ ] All filters are optional and can be combined
- [ ] Filters are sent as query parameters on `GET /api/v1/projects`
