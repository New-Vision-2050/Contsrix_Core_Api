# Internal Procedure Settings - API Updates & New Endpoints

> Last Updated: June 15, 2026
> Covers: EmployeeTask module refactored to use self-referencing `ProcedureSetting` with `InternalProcedureSettings` (child rows)

---

## Architecture Change Summary

### Before
- `ProcedureSettingType` had separate cases: `EmployeeTaskRequest`, `EmployeeTaskExtension`, `EmployeeTaskApproval`
- Each was a standalone `ProcedureSetting` row
- `internal_process_type_id` column on `employee_task_requests`

### After
- `ProcedureSettingType` simplified to categories: `employee_task`, `client_request`, `price_offer`, `contract`, `meeting`
- One **parent** `ProcedureSetting` per category (`parent_id = NULL`)
- Multiple **child** `InternalProcedureSettings` under each parent (`parent_id = parent.id`, `form = 'start_task' | 'extend_task_time' | 'send_for_approval' | ...`)
- Each child has its own `name`, `form`, `conditions`, `steps`, `appears_before_id`, `appears_after_id`

---

## New Variables

Add these to your Postman collection:

```json
{
  "key": "internal_procedure_setting_id",
  "value": "uuid-of-child-procedure-setting",
  "type": "string",
  "description": "Child ProcedureSetting ID (form-specific)"
}
```

---

## Updated Endpoints

### 1. Get Approval Responsibles (Preview) - UPDATED

**Method:** `GET`  
**Endpoint:** `/api/v1/procedure-settings/approval-responsibles`  
**Change:** Type parameter now uses category value. Optional `form_key` added for child resolution.

**Query Parameters:**
| Key | Required | Description |
|-----|----------|-------------|
| `type` | Yes | Category: `employee_task`, `client_request`, `price_offer`, `contract`, `meeting` |
| `form_key` | No | Form key for child resolution: `start_task`, `extend_task_time`, `send_for_approval`, `cancel_task`, `confirm_location`, `assign_other_employee`, `attach_attachments` |

**Example Request:**
```http
GET {{url}}/api/v1/procedure-settings/approval-responsibles?type=employee_task&form_key=start_task
```

**Example Response:**
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Approval responsibles retrieved successfully",
  "payload": {
    "auto_approve": false,
    "step": {
      "id": 12,
      "name": "مدير القسم",
      "step_order": 1
    },
    "action_takers": [
      { "user_id": "admin-uuid-1", "name": "احمد طه زكريا" }
    ]
  }
}
```

---

### 2. Create Task Request (Store) - UPDATED

**Method:** `POST`  
**Endpoint:** `/api/v1/employee-tasks`  
**Change:** Removed `internal_process_type_id`. No longer needed — the `start_task` form child is auto-resolved internally.

**Request Body:**
```json
{
  "title": "تجهيز الموقع",
  "description": "تجهيز موقع العمل للبدء في المشروع",
  "project_id": "project-uuid-here",
  "approval_responsible_id": "admin-uuid-here",
  "assignment_responsible_id": "employee-uuid-here",
  "duration_hours": 8.0,
  "task_date": "2026-06-20",
  "task_latitude": 24.7136,
  "task_longitude": 46.6753,
  "radius_meters": 500,
  "notes": "يرجى مراجعة الموقع قبل البدء"
}
```

> **Removed field:** `internal_process_type_id` — no longer part of the API.

**Response:** Same as before — returns created task with `status: pending` or `approved` (if auto-approve).

---

### 3. Request Task Completion Approval (Send for Approval) - UPDATED

**Method:** `POST`  
**Endpoint:** `/api/v1/employee-tasks/{task_id}/request-approval`  
**Change:** Now accepts optional `internal_procedure_setting_id` for explicit child selection.

**Request Body (multipart/form-data):**
```
notes: optional text
file: optional file upload (max 20MB)
internal_procedure_setting_id: optional UUID — specific child procedure setting to use
```

**Example with explicit child:**
```http
POST {{url}}/api/v1/employee-tasks/{{task_id}}/request-approval
Content-Type: multipart/form-data

notes=Task completed, please review
internal_procedure_setting_id=child-uuid-2
file=@report.pdf
```

**Behavior:**
- If `internal_procedure_setting_id` provided → loads that exact child (useful when multiple children share the same `form` like `send_for_approval`)
- If not provided → resolves by `form = 'send_for_approval'` (fallback, works for single child)

**Response:**
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Task approval request submitted successfully",
  "payload": {
    "id": "approval-uuid",
    "task_id": "task-uuid",
    "notes": "Task completed, please review",
    "status": "pending",
    "current_step": {
      "id": 12,
      "name": "مدير القسم",
      "step_order": 1
    },
    "media": []
  }
}
```

---

### 4. Create Extension Request - UPDATED

**Method:** `POST`  
**Endpoint:** `/api/v1/employee-tasks/{task_id}/extension-requests`  
**Change:** Now accepts optional `internal_procedure_setting_id` for explicit child selection.

**Request Body:**
```json
{
  "additional_hours": 2.5,
  "reason": "احتاج وقت اضافي لتجهيز الموقع",
  "internal_procedure_setting_id": "child-uuid-1"
}
```

**Behavior:**
- If `internal_procedure_setting_id` provided → loads that exact child
- If not provided → resolves by `form = 'extend_task_time'` (fallback)

**Response:**
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Extension request submitted successfully",
  "payload": {
    "id": "extension-uuid",
    "task_id": "task-uuid",
    "additional_hours": "2.50",
    "reason": "احتاج وقت اضافي لتجهيز الموقع",
    "status": "pending",
    "current_step": {
      "id": 15,
      "name": "مدير المشروع",
      "step_order": 1
    }
  }
}
```

---

## New Endpoints

### 5. Get Available Actions (Mobile)

**Method:** `GET`  
**Endpoint:** `/api/v1/employee-tasks/{task_id}/available-actions`  
**Auth:** Employee Bearer token  
**New:** Returns all active internal procedure settings (child rows) available for a given task, sorted by ordering constraints.

**Example Request:**
```http
GET {{url}}/api/v1/employee-tasks/{{task_id}}/available-actions
Authorization: Bearer {{token}}
```

**Example Response:**
```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Available actions retrieved successfully",
  "payload": [
    {
      "id": "child-uuid-1",
      "name": "تمديد وقت المهمة",
      "form": {
        "key": "extend_task_time",
        "label_ar": "تمديد وقت المهمة"
      },
      "conditions": [
        { "key": "AllowDuringShift", "type": "boolean", "label_ar": "السماح أثناء الدوام" }
      ],
      "appears_before_id": null,
      "appears_after_id": null,
      "sort_order": 1
    },
    {
      "id": "child-uuid-2",
      "name": "تأكيد دخول الموقع",
      "form": {
        "key": "confirm_location",
        "label_ar": "تأكيد الموقع"
      },
      "conditions": [
        { "key": "ApplyToAllBranches", "type": "boolean", "label_ar": "يطبق على جميع الفروع" }
      ],
      "appears_before_id": "child-uuid-3",
      "appears_after_id": null,
      "sort_order": 2
    },
    {
      "id": "child-uuid-3",
      "name": "تأكيد خروج الموقع",
      "form": {
        "key": "confirm_location",
        "label_ar": "تأكيد الموقع"
      },
      "conditions": [
        { "key": "ApplyToAllBranches", "type": "boolean", "label_ar": "يطبق على جميع الفروع" }
      ],
      "appears_before_id": null,
      "appears_after_id": "child-uuid-2",
      "sort_order": 3
    },
    {
      "id": "child-uuid-4",
      "name": "ارسال للاعتماد",
      "form": {
        "key": "send_for_approval",
        "label_ar": "ارسال للاعتماد"
      },
      "conditions": [
        { "key": "AllowDuringShift", "type": "boolean", "label_ar": "السماح أثناء الدوام" }
      ],
      "appears_before_id": null,
      "appears_after_id": null,
      "sort_order": 4
    }
  ]
}
```

**Use Case:** Mobile app renders a list of action buttons. Each item has a unique `id` that the app passes back when the user triggers that action.

> **Key insight:** Two children can share the same `form` key (e.g., two `confirm_location` entries with different names). The mobile app must send back the specific `id` of the tapped item, not just the `form` key.

---

## Admin Endpoints (New)

### 6. List Internal Procedure Settings

**Method:** `GET`  
**Endpoint:** `/api/v1/procedure-settings/{procedure_setting_id}/internal-procedures`  
**Auth:** Admin Bearer token  
**New:** CRUD for internal procedure settings (child rows) under a parent.

**Example Request:**
```http
GET {{url}}/api/v1/procedure-settings/{{procedure_setting_id}}/internal-procedures
Authorization: Bearer {{admin_token}}
```

**Example Response:**
```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Internal procedure settings retrieved successfully",
  "payload": [
    {
      "id": "child-uuid-1",
      "name": "بدء المهمة",
      "form": "start_task",
      "conditions": [],
      "sort_order": 1,
      "appears_before_id": null,
      "appears_after_id": null,
      "is_active": true
    },
    {
      "id": "child-uuid-2",
      "name": "تمديد وقت المهمة",
      "form": "extend_task_time",
      "conditions": [
        { "key": "AllowDuringShift", "value": true }
      ],
      "sort_order": 2,
      "appears_before_id": null,
      "appears_after_id": null,
      "is_active": true
    }
  ]
}
```

---

### 7. Create Internal Procedure Setting

**Method:** `POST`  
**Endpoint:** `/api/v1/procedure-settings/{procedure_setting_id}/internal-procedures`  
**New**

**Request Body:**
```json
{
  "name": "تأكيد خروج الموقع",
  "form": "confirm_location",
  "conditions": [
    { "key": "ApplyToAllBranches", "value": true }
  ],
  "appears_before_id": null,
  "appears_after_id": "child-uuid-of-enter-location",
  "sort_order": 3,
  "is_active": true
}
```

> **Note:** `form` must be one of: `start_task`, `extend_task_time`, `send_for_approval`, `cancel_task`, `confirm_location`, `assign_other_employee`, `attach_attachments`

**Response:**
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Internal procedure setting created successfully",
  "payload": {
    "id": "new-child-uuid",
    "name": "تأكيد خروج الموقع",
    "form": "confirm_location",
    "conditions": [
      { "key": "ApplyToAllBranches", "value": true }
    ],
    "sort_order": 3,
    "appears_before_id": null,
    "appears_after_id": "child-uuid-of-enter-location"
  }
}
```

---

### 8. Update Internal Procedure Setting

**Method:** `PUT`  
**Endpoint:** `/api/v1/procedure-settings/{procedure_setting_id}/internal-procedures/{id}`  
**New**

**Request Body:**
```json
{
  "name": "تأكيد خروج الموقع - نهائي",
  "form": "confirm_location",
  "conditions": [
    { "key": "ApplyToAllBranches", "value": true }
  ],
  "appears_before_id": null,
  "appears_after_id": "child-uuid-of-enter-location",
  "sort_order": 3,
  "is_active": true
}
```

---

### 9. Delete Internal Procedure Setting

**Method:** `DELETE`  
**Endpoint:** `/api/v1/procedure-settings/{procedure_setting_id}/internal-procedures/{id}`  
**New**

**Response:**
```json
{
  "code": "SUCCESS_WITH_SINGLE_PAYLOAD_OBJECT",
  "message": "Internal procedure setting deleted successfully",
  "payload": null
}
```

---

### 10. Get Available Forms (Definitions)

**Method:** `GET`  
**Endpoint:** `/api/v1/procedure-settings/{procedure_setting_id}/available-forms`  
**New:** Returns all `InternalProcessForm` definitions applicable to the parent's category type. Used by the admin UI when creating a new internal procedure setting.

**Example Request:**
```http
GET {{url}}/api/v1/procedure-settings/{{procedure_setting_id}}/available-forms
```

**Example Response:**
```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": "Available forms retrieved successfully",
  "payload": [
    {
      "key": "start_task",
      "label_ar": "بدء المهمة",
      "conditions": [
        { "key": "AllowDuringShift", "type": "boolean", "label_ar": "السماح أثناء الدوام" },
        { "key": "AllowOutsideShift", "type": "boolean", "label_ar": "السماح خارج الدوام" },
        { "key": "AllowOnHolidays", "type": "boolean", "label_ar": "السماح في الأجازات" },
        { "key": "ApplyToAllBranches", "type": "boolean", "label_ar": "يطبق على جميع الفروع" },
        { "key": "HasTaskDuration", "type": "boolean", "label_ar": "يحتوي على مدة مهمة" },
        { "key": "MaxDurationHours", "type": "number", "label_ar": "الحد الأقصى لساعات المهمة" }
      ]
    },
    {
      "key": "extend_task_time",
      "label_ar": "تمديد وقت المهمة",
      "conditions": [
        { "key": "AllowDuringShift", "type": "boolean", "label_ar": "السماح أثناء الدوام" },
        { "key": "AllowOutsideShift", "type": "boolean", "label_ar": "السماح خارج الدوام" },
        { "key": "AllowOnHolidays", "type": "boolean", "label_ar": "السماح في الأجازات" },
        { "key": "ApplyToAllBranches", "type": "boolean", "label_ar": "يطبق على جميع الفروع" },
        { "key": "HasTaskDuration", "type": "boolean", "label_ar": "يحتوي على مدة مهمة" },
        { "key": "MaxDurationHours", "type": "number", "label_ar": "الحد الأقصى لساعات المهمة" }
      ]
    },
    {
      "key": "send_for_approval",
      "label_ar": "ارسال للاعتماد",
      "conditions": [
        { "key": "AllowDuringShift", "type": "boolean", "label_ar": "السماح أثناء الدوام" },
        { "key": "ApplyToAllBranches", "type": "boolean", "label_ar": "يطبق على جميع الفروع" }
      ]
    }
  ]
}
```

---

## Mobile Flow Example

### Scenario: Employee with a task that needs location confirmation twice (enter + exit)

```
Step 1: GET /employee-tasks/{{task_id}}/available-actions
→ Returns:
    [ { id: "uuid-enter", name: "تأكيد دخول الموقع", form: "confirm_location" },
      { id: "uuid-extend", name: "تمديد وقت المهمة", form: "extend_task_time" },
      { id: "uuid-exit",  name: "تأكيد خروج الموقع",  form: "confirm_location" },
      { id: "uuid-approval", name: "ارسال للاعتماد", form: "send_for_approval" } ]

Step 2: User taps "تأكيد دخول الموقع"
→ Frontend stores: internal_procedure_setting_id = "uuid-enter"
→ (Any API that triggers a procedure action sends this ID)

Step 3: User later taps "تأكيد خروج الموقع"
→ Frontend stores: internal_procedure_setting_id = "uuid-exit"
→ Different child, different steps, different conditions
```

---

## Removed / Deprecated Endpoints

| Old Endpoint | Status | Replacement |
|--------------|--------|-------------|
| `GET /procedure-settings/approval-responsibles?type=employee_task_request` | **Changed** | Use `type=employee_task&form_key=start_task` |
| `GET /procedure-settings/approval-responsibles?type=employee_task_extension` | **Changed** | Use `type=employee_task&form_key=extend_task_time` |
| `GET /procedure-settings/approval-responsibles?type=employee_task_completion_approval` | **Changed** | Use `type=employee_task&form_key=send_for_approval` |
| `POST /employee-tasks` with `internal_process_type_id` | **Removed** | Field no longer accepted |
| `/internal-process-types/*` | **Deprecated** | Replaced by `/procedure-settings/{id}/internal-procedures/*` |

---

## Enum Reference

### ProcedureSettingType (Categories)
```
employee_task  | client_request | price_offer | contract | meeting
```

### InternalProcessForm (Forms)
```
start_task           | extend_task_time      | send_for_approval
assign_other_employee | cancel_task            | confirm_location
attach_attachments
```

### InternalProcessCondition (Conditions per form)
```
AllowDuringShift | AllowOutsideShift | AllowOnHolidays
ApplyToAllBranches | HasTaskDuration | MaxDurationHours | MaxAttachments
```

---

**Collection Files:**
- `EmployeeTask_API.postman_collection.json` (updated)
- `ProcedureSetting_API.postman_collection.json` (updated)
- `InternalProcedureSettings_API_UPDATES.md` (this file)
