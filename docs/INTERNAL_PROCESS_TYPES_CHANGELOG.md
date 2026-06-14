# Internal Process Types — ما تم إضافته وتعديله

> **التاريخ:** 14 يونيو 2026  
> **النطاق:** أنواع الإجراءات الداخلية + فصل Procedure Workflows لمهام العمل

---

## ملخص تنفيذي

تم تنفيذ ثلاثة محاور:

1. **موديول جديد** `Shared/InternalProcessType` — أنواع إجراءات داخلية polymorphic يملأها الأدمن ويظهرها الموبايل.
2. **فصل الـ workflows** — كل إجراء من إجراءات مهام العمل له `ProcedureSetting` مستقل.
3. **Seeders** — تهيئة تلقائية لـ ProcedureSettings عند بدء النظام.

---

## 1. ميزات جديدة (Added)

### 1.1 موديول `InternalProcessType`

**المسار:** `modules/Shared/InternalProcessType/`

| المكوّن | الوصف |
|---------|--------|
| `internal_process_types` (جدول) | أنواع الإجراءات الداخلية لكل شركة |
| `InternalProcessEntityType` | `employee_task`, `leave_request` (مستقبلاً) |
| `InternalProcessCondition` | 5 شروط عامة لكل entity |
| Admin CRUD | إنشاء / تعديل / حذف / قائمة |
| Mobile endpoint | قائمة الأنواع النشطة للـ dropdown |

**الشروط (`conditions` في الـ API / مخزّنة داخل `settings` JSON):**

| Key | النوع | المعنى |
|-----|-------|--------|
| `allow_during_shift` | bool | موظف داخل الدوام |
| `allow_outside_shift` | bool | موظف خارج الدوام |
| `allow_on_holidays` | bool | مسموح في العطلات |
| `apply_to_all_branches` | bool | تعمل في جميع الفروع |
| `has_task_duration` | bool | مدة المهمة |
| `max_duration_hours` | int\|null | أقصى مدة بالساعات |
| `max_attachments` | int\|null | أقصى عدد مرفقات |

**النماذج (`forms` — Enum `InternalProcessForm`):**

| Key | الاسم العربي |
|-----|-------------|
| `start_task` | بدء المهمة |
| `assign_other_employee` | تحويل لموظف اخر |
| `extend_task_time` | تمديد وقت المهمة |
| `send_for_approval` | ارسال للاعتماد |
| `cancel_task` | الغاء المهمة |
| `confirm_location` | تأكيد الموقع |
| `attach_attachments` | ارفاق مرفقات |

**ترتيب الإجراء (`ordering`):**

| Key | النوع | المعنى |
|-----|-------|--------|
| `appears_before_id` | uuid\|null | يظهر قبل — ID نوع إجراء آخر |
| `appears_after_id` | uuid\|null | يظهر بعد — ID نوع إجراء آخر |

**ملفات الموديول:**

```
modules/Shared/InternalProcessType/
├── Controllers/
│   ├── AdminInternalProcessTypeController.php
│   └── InternalProcessTypeController.php
├── Database/Migrations/
│   └── 2026_06_14_000000_create_internal_process_types_table.php
├── DTO/
│   ├── CreateInternalProcessTypeDTO.php
│   └── UpdateInternalProcessTypeDTO.php
├── Enums/
│   ├── InternalProcessEntityType.php
│   ├── InternalProcessCondition.php
│   └── InternalProcessForm.php
├── Support/InternalProcessTypePayload.php
├── Models/InternalProcessType.php
├── Presenters/InternalProcessTypePresenter.php
├── Repositories/InternalProcessTypeRepository.php
├── Requests/
│   ├── CreateInternalProcessTypeRequest.php
│   └── UpdateInternalProcessTypeRequest.php
├── Resources/routes/api.php
├── Services/InternalProcessTypeCRUDService.php
├── Providers/InternalProcessTypeServiceProvider.php
└── module.json
```

---

### 1.2 APIs جديدة

**Base:** `/api/v1`  
**Auth:** `Bearer {{token}}`  
**Tenant:** `X-Company-Id: {{company_id}}` (إن وُجد)

#### Admin

| Method | Endpoint | الوصف |
|--------|----------|--------|
| `GET` | `/admin/internal_procedure_setting_forms` | النماذج + شروط كل نموذج |
| `GET` | `/admin/forms_conditions` | كل الشروط (key + type) |
| `GET` | `/admin/internal_procedure_settings?entity_type=employee_task` | قائمة (paginated) |
| `POST` | `/admin/internal_procedure_settings` | إنشاء إعداد (form واحد) |
| `PUT` | `/admin/internal_procedure_settings/{id}` | تعديل |
| `DELETE` | `/admin/internal_procedure_settings/{id}` | حذف |

#### Mobile / Employee

| Method | Endpoint | الوصف |
|--------|----------|--------|
| `GET` | `/internal-process-types?entity_type=employee_task` | أنواع نشطة فقط |

**مثال Create:**

```json
{
  "entity_type": "employee_task",
  "name": "تمديد وقت مهمة العمل",
  "form": "extend_task_time",
  "conditions": {
    "allow_during_shift": true,
    "allow_outside_shift": false,
    "allow_on_holidays": false,
    "apply_to_all_branches": true,
    "has_task_duration": true,
    "max_duration_hours": 8
  },
  "ordering": {
    "appears_before_id": null,
    "appears_after_id": null
  }
}
```

**مثال `GET /admin/internal_procedure_setting_forms`:**

```json
{
  "payload": [
    {
      "key": "extend_task_time",
      "label_ar": "تمديد وقت المهمة",
      "conditions": [
        { "key": "allow_during_shift", "type": "bool", "label_ar": "موظف داخل الدوام" },
        { "key": "max_duration_hours", "type": "int", "label_ar": "أقصى مدة بالساعات" }
      ]
    }
  ]
}
```

**مثال Response (setting):**

```json
{
  "payload": {
    "id": "uuid",
    "name": "تمديد وقت مهمة العمل",
    "form": { "key": "extend_task_time", "label_ar": "تمديد وقت المهمة" },
    "conditions": {
      "allow_during_shift": true,
      "max_duration_hours": 8
    },
    "ordering": {
      "appears_before_id": null,
      "appears_after_id": null
    },
    "created_at": "2026-06-14 10:00:00",
    "updated_at": "2026-06-14 10:00:00"
  }
}
```

---

### 1.3 Migrations جديدة

| Migration | الجدول | التغيير |
|-----------|--------|---------|
| `2026_06_14_000000_create_internal_process_types_table` | `internal_process_types` | جدول جديد (يُنشأ أولاً — `dropIfExists` ثم `create`) |
| `2026_06_14_000001_add_internal_process_type_id_to_employee_task_requests` | `employee_task_requests` | `internal_process_type_id` FK (مع `hasColumn` guard) |
| `2026_06_14_000002_add_procedure_setting_id_to_extension_and_approval_requests` | `employee_task_extension_requests`, `employee_task_approval_requests` | `procedure_setting_id` FK (مع `hasColumn` guard) |

---

### 1.4 ProcedureSettingType — Cases جديدة

```php
case EmployeeTaskExtension = 'employee_task_extension';          // تمديد مدة
case EmployeeTaskApproval  = 'employee_task_completion_approval'; // اعتماد إتمام
```

| Type | الاستخدام |
|------|-----------|
| `employee_task_request` | طلب مهمة (موجود مسبقاً) |
| `employee_task_extension` | طلب تمديد مدة |
| `employee_task_completion_approval` | إرسال للاعتماد / اعتماد إتمام |
| `client_request` | طلب عميل (موجود مسبقاً) |

> `employee_task_transfer` (تحويل موظف) **مؤجل** — لا API له حالياً.

---

### 1.5 Seeders جديدة

| Seeder | المسار | الوظيفة |
|--------|--------|---------|
| `EmployeeTaskWorkflowSeeder` | `modules/ProcedureSetting/Database/Seeders/` | ينشئ لكل شركة: `employee_task_request`, `employee_task_extension`, `employee_task_completion_approval` |
| `ClientRequestWorkflowSeeder` | نفس المسار | ينشئ لكل شركة: `client_request` |

**تشغيل:**

```bash
php artisan migrate
php artisan db:seed --class="Modules\ProcedureSetting\Database\Seeders\EmployeeTaskWorkflowSeeder"
php artisan db:seed --class="Modules\ProcedureSetting\Database\Seeders\ClientRequestWorkflowSeeder"
```

> بعد الـ seed، لازم إضافة **خطوات الموافقة (steps)** من Admin UI لكل procedure جديد.

---

### 1.6 Method جديد في ProcedureWorkflowService

```php
resolveProcedureSettingForBranch(
    string $procedureType,
    string $companyId,
    ?string $branchId,
): ?ProcedureSetting
```

يحل الـ procedure المناسب حسب النوع + الشركة + فرع الموظف (عبر `workFlow.managementHierarchies`).

---

## 2. تعديلات على كود موجود (Modified)

### 2.1 EmployeeTask — ربط بنوع الإجراء

| الملف | التعديل |
|-------|---------|
| `CreateEmployeeTaskRequest.php` | validation: `internal_process_type_id` (nullable, uuid, exists) |
| `CreateEmployeeTaskRequestDTO.php` | حقل `internalProcessTypeId` |
| `EmployeeTaskController.php` | تمرير `internal_process_type_id` عند الإنشاء |
| `EmployeeTaskRequest.php` | `internal_process_type_id` في fillable + relation `internalProcessType()` |
| `EmployeeTaskRequestPresenter.php` | عرض `internal_process_type_id` و `internal_process_type` |

**مثال Create Task:**

```json
{
  "title": "فحص نظام الإنذار",
  "internal_process_type_id": "uuid-from-dropdown",
  "duration_hours": 4,
  "task_date": "2026-06-15",
  "task_latitude": 24.7136,
  "task_longitude": 46.6753
}
```

---

### 2.2 فك وراثة Procedure — Extension

**قبل:** التمديد كان يرث `procedure_setting_id` من المهمة الأم.

**بعد:** يستخدم procedure مستقل من نوع `employee_task_extension`.

| الملف | التعديل |
|-------|---------|
| `EmployeeTaskExtensionService.php` | `resolveExtensionProcedureSetting()` + حفظ `procedure_setting_id` على الـ extension |
| `EmployeeTaskExtensionWorkflowService.php` | `advance()` / `reject()` يستخدمان `$extension->procedure_setting_id` |
| `EmployeeTaskExtensionRequest.php` | `procedure_setting_id` في fillable |

---

### 2.3 فك وراثة Procedure — Approval

**قبل:** اعتماد الإتمام كان يرث procedure المهمة الأم.

**بعد:** يستخدم procedure مستقل من نوع `employee_task_completion_approval`.

| الملف | التعديل |
|-------|---------|
| `EmployeeTaskApprovalService.php` | `resolveApprovalProcedureSetting()` + حفظ `procedure_setting_id` على الـ approval |
| `EmployeeTaskApprovalService::approve()` | يستخدم `$approval->procedure_setting_id` في `workflow->advance()` |
| `EmployeeTaskApprovalRequest.php` | `procedure_setting_id` في fillable |

---

### 2.4 ProcedureSettingType.php

- تحديث التعليق التوثيقي
- إضافة `EmployeeTaskExtension` و `EmployeeTaskApproval`
- الـ validation في `CreateProcedureSettingRequest` يشمل الأنواع الجديدة تلقائياً عبر `ProcedureSettingType::values()`

---

## 3. ما لم يتغير (Unchanged)

| العنصر | الملاحظة |
|--------|----------|
| `EmployeeTaskRequestService` (طلب المهمة) | ما زال يستخدم `employee_task_request` + `ProcessWorkflowService` |
| `WorkFlowForBranchesSeeder` | يمشي على كل cases تلقائياً — لا يحتاج تعديل |
| Inbox unified shape | `task_request`, `extension_request`, `task_approval` — نفس الشكل |
| Lifecycle (start/pause/resume/end) | بدون تغيير |

---

## 4. العلاقات والتدفق

```
internal_process_types
  └── entity_type: employee_task
        └── settings: { 5 شروط }

employee_task_requests
  └── internal_process_type_id  →  نوع المهمة (صيانة / فحص / ...)

ProcedureSetting (لكل إجراء workflow منفصل)
  ├── employee_task_request            → طلب مهمة
  ├── employee_task_extension          → تمديد
  └── employee_task_completion_approval → اعتماد إتمام

employee_task_extension_requests
  └── procedure_setting_id  →  workflow التمديد (مستقل)

employee_task_approval_requests
  └── procedure_setting_id  →  workflow الاعتماد (مستقل)
```

---

## 5. Postman

ملف جاهز للاستيراد (إن وُجد في المشروع):

`InternalProcessType_API.postman_collection.json`

**Variables:**

- `url`, `token`, `company_id`, `internal_process_type_id`

---

## 6. قرارات مفتوحة للفريق

| السؤال | الوضع الحالي |
|--------|--------------|
| `internal_process_type_id` إجباري؟ | **nullable** — اختياري عند إنشاء المهمة |
| تحويل موظف (`employee_task_transfer`) | **مؤجل** — لا API |
| Validation أن النوع `entity_type = employee_task` عند ربطه بمهمة | غير مفعّل حالياً — يُنصح بإضافته لاحقاً |

---

## 7. أوامر التشغيل السريعة

### تنظيف السيرفر (مرة واحدة قبل الـ migrate — إذا فشلت migration سابقاً)

```sql
-- لكل tenant DB
SET FOREIGN_KEY_CHECKS=0;
ALTER TABLE employee_task_requests DROP COLUMN IF EXISTS internal_process_type_id;
DROP TABLE IF EXISTS internal_process_types;
SET FOREIGN_KEY_CHECKS=1;

-- إذا سُجّلت migration فاشلة في جدول migrations
DELETE FROM migrations WHERE migration LIKE '2026_06_14_%internal_process%';
```

> **ملاحظة:** إذا كان MySQL لا يدعم `DROP COLUMN IF EXISTS`، استخدم:
> `ALTER TABLE employee_task_requests DROP COLUMN internal_process_type_id;` فقط إذا العمود موجود.

```bash
# بعد نشر الكود المُصلَح
php artisan migrate --force
```

### Seeders

```bash
# Migrations (بيئة محلية)
php artisan migrate

# Seed workflows
php artisan db:seed --class="Modules\ProcedureSetting\Database\Seeders\EmployeeTaskWorkflowSeeder"
php artisan db:seed --class="Modules\ProcedureSetting\Database\Seeders\ClientRequestWorkflowSeeder"

# (اختياري) ربط workflows بالفروع
php artisan db:seed --class="Modules\ProcedureSetting\Database\Seeders\WorkFlowForBranchesSeeder"
```

---

*آخر تحديث: 14 يونيو 2026*
