# Medical Insurance Subscriptions in `/user_privileges`

## Overview

تم إضافة حقل `subscriptions` في استجابة `GET /user_privileges/user/{id}` و `POST /user_privileges` و `PUT /user_privileges/{id}` لنوع الامتياز `health_insurance` فقط. التعديل يعيد استخدام نفس السيرفس الموجود في `POST /medical-insurances/subscriptions` بدون تكرار لأي بيزنس لوجيك.

---

## الملفات المعدلة

| الملف | التعديل |
|---|---|
| `modules/UserInfo/UserPrivilege/Controllers/UserPrivilegeController.php` | إضافة `MedicalInsuranceSubscriptionCRUDService` + معالجة الـ subscriptions في `store()` و `update()` + جلب الـ subscriptions مسبقاً في `index()` لتجنب N+1 |
| `modules/UserInfo/UserPrivilege/Presenters/UserPrivilegePresenter.php` | إضافة حقل `subscriptions` فقط لنوع `health_insurance` باستخدام `MedicalInsuranceSubscriptionPresenter` |
| `modules/UserInfo/UserPrivilege/Requests/CreateUserPrivilegeRequest.php` | إضافة validation rules للـ `subscriptions` + ميثود `createSubscriptionDTOs()` |
| `modules/UserInfo/UserPrivilege/Requests/UpdateUserPrivilegeRequest.php` | إضافة validation rules للـ `subscriptions` + ميثود `createSubscriptionDTOs()` |

---

## شكل الـ Response

### امتياز من نوع Health Insurance

```json
{
    "success": true,
    "data": [
        {
            "id": "31eb7f43-2810-436a-b438-e57b44ba87e0",
            "type_privilege_id": "913f80bf-2883-4948-a2e3-50be75bdad9b",
            "type_allowance_code": "saving",
            "period_id": null,
            "type_privilege": {
                "id": "913f80bf-2883-4948-a2e3-50be75bdad9b",
                "name": "فردي"
            },
            "type_allowance": {
                "id": "913f80bf-2883-4948-a2e3-50be75bdad9c",
                "name": "توفير",
                "code": "saving"
            },
            "charge_amount": "3000",
            "description": "Medical Insurance",
            "period": null,
            "privilege": {
                "id": "31eb7f43-2810-436a-b438-e57b44ba87e0",
                "name": "تأمين طبي",
                "type": "health_insurance",
                "card_fields": { "fields": ["..."] }
            },
            "medical_insurance": {
                "id": "9a9d9fe1-5da2-4568-8a35-6249f21022e6",
                "name": "AXA Health Insurance",
                "policy_number": "POL-12345",
                "provider": "AXA",
                "start_date": "2026-01-01",
                "end_date": "2026-12-31",
                "value": 5000.0,
                "individuals_count": 5,
                "status": 1
            },
            "card_fields": { "fields": ["..."] },
            "subscriptions": [
                {
                    "id": "1b28d869-96b2-454e-8cae-15c04f52f480",
                    "user_id": "313e35eb-9e59-471d-bf97-5c3609cbca64",
                    "medical_insurance_id": "9a9d9fe1-5da2-4568-8a35-6249f21022e6",
                    "medical_insurance_category_id": "b2c86ad5-a30b-4bb1-9b65-6c7e8fcdf95c",
                    "amount": 3000,
                    "subscription_no": "SUB-024",
                    "subscription_type": "family",
                    "status": 1,
                    "user": {
                        "id": "313e35eb-9e59-471d-bf97-5c3609cbca64",
                        "name": "Ahmed Mohamed"
                    },
                    "medical_insurance": {
                        "id": "9a9d9fe1-5da2-4568-8a35-6249f21022e6",
                        "name": "AXA Health Insurance",
                        "policy_number": "POL-12345"
                    },
                    "medical_insurance_category": {
                        "id": "b2c86ad5-a30b-4bb1-9b65-6c7e8fcdf95c",
                        "name": "الفئة الذهبية",
                        "type": "gold",
                        "coverage_limit": 100000,
                        "description": "تغطية شاملة"
                    },
                    "family_members": [
                        {
                            "id": "abc-123",
                            "name": "فاطمة أحمد",
                            "national_id": "1234567890",
                            "relation": "زوجة",
                            "amount": 1500,
                            "subscription_no": "SUB-001-F21",
                            "created_at": "2026-06-10 15:44:06",
                            "updated_at": "2026-06-10 15:44:06"
                        }
                    ],
                    "created_at": "2026-06-10 15:44:06",
                    "updated_at": "2026-06-10 15:44:06"
                }
            ]
        }
    ]
}
```

### امتياز من نوع آخر (بدون subscriptions)

```json
{
    "success": true,
    "data": [
        {
            "id": "31eb7f43-2810-436a-b438-e57b44ba87e0",
            "type_privilege_id": "913f80bf-2883-4948-a2e3-50be75bdad9b",
            "type_allowance_code": "constant",
            "charge_amount": "500",
            "description": "بدل سيارة",
            "privilege": {
                "id": "31eb7f43-2810-436a-b438-e57b44ba87e0",
                "name": "بدل سيارة",
                "type": "car_allowance",
                "card_fields": { "fields": ["..."] }
            },
            "medical_insurance": null,
            "card_fields": { "fields": ["..."] }
        }
    ]
}
```

> ⚠️ ملحوظة: حقل `subscriptions` لا يظهر إطلاقاً في الـ response للأنواع الأخرى غير `health_insurance`.

---

## شكل الـ Body في Postman

### `GET /api/v1/user_privileges/user/{user_id}`

```
No body — GET request
```

Query params (اختياري):

| Param | Type | Example |
|---|---|---|
| `page` | int | `1` |
| `per_page` | int | `10` |
| `type` | string | `health_insurance` |
| `type_privilege` | string | `Individual` / `Family` |
| `type_privilege_id` | uuid | `913f80bf-...` |

---

### `POST /api/v1/user_privileges`

```json
{
    "user_id": "313e35eb-9e59-471d-bf97-5c3609cbca64",
    "privilege_id": "31eb7f43-2810-436a-b438-e57b44ba87e0",
    "type_privilege_id": "913f80bf-2883-4948-a2e3-50be75bdad9b",
    "type_allowance_code": "saving",
    "charge_amount": "3000",
    "description": "Medical Insurance",
    "subscriptions": [
        {
            "medical_insurance_id": "9a9d9fe1-5da2-4568-8a35-6249f21022e6",
            "medical_insurance_category_id": "b2c86ad5-a30b-4bb1-9b65-6c7e8fcdf95c",
            "amount": 3000,
            "subscription_no": "SUB-024",
            "subscription_type": "family",
            "status": 1,
            "family_members": [
                {
                    "name": "فاطمة أحمد",
                    "national_id": "1234567890",
                    "relation": "زوجة",
                    "amount": 1500,
                    "subscription_no": "SUB-001-F21"
                },
                {
                    "name": "محمد أحمد",
                    "national_id": "9876543210",
                    "relation": "ابن",
                    "amount": 800,
                    "subscription_no": "SUB-001-1F21"
                }
            ]
        }
    ]
}
```

> ℹ️ `user_id` في الـ subscriptions يتم أخذه تلقائياً من الـ user_id الأساسي في الـ request — مش محتاج تبعتّه.

---

### `PUT /api/v1/user_privileges/{privilege_id}`

```json
{
    "type_allowance_code": "saving",
    "subscriptions": [
        {
            "user_id": "313e35eb-9e59-471d-bf97-5c3609cbca64",
            "medical_insurance_id": "9a9d9fe1-5da2-4568-8a35-6249f21022e6",
            "medical_insurance_category_id": "b2c86ad5-a30b-4bb1-9b65-6c7e8fcdf95c",
            "amount": 3000,
            "subscription_no": "SUB-024",
            "subscription_type": "family",
            "status": 1,
            "family_members": [
                {
                    "name": "فاطمة أحمد",
                    "national_id": "1234567890",
                    "relation": "زوجة",
                    "amount": 1500,
                    "subscription_no": "SUB-001-F21"
                }
            ]
        }
    ]
}
```

> ⚠️ في الـ update، `user_id` **مطلوب** داخل كل اشتراك لأنه مش موجود في الـ request الأساسي.

---

### الحقول المدعومة في `subscriptions`

| الحقل | النوع | مطلوب؟ | القيم المسموحة |
|---|---|---|---|
| `user_id` | uuid | ✅ في update فقط | موجود في users |
| `medical_insurance_id` | uuid | ✅ | موجود في medical_insurances |
| `medical_insurance_category_id` | uuid | ❌ | موجود في medical_insurance_categories |
| `amount` | numeric | ✅ | `>= 0` |
| `subscription_no` | string | ✅ | max 255, فريد |
| `subscription_type` | string | ❌ (default: `individual`) | `individual` / `family` |
| `status` | integer | ❌ (default: `1`) | `-1`, `0`, `1` |
| `family_members` | array | ❌ | — |

#### حقول `family_members`

| الحقل | النوع | مطلوب؟ |
|---|---|---|
| `name` | string | ✅ مع family_members |
| `national_id` | string | ✅ مع family_members |
| `relation` | string | ✅ مع family_members |
| `amount` | numeric | ✅ مع family_members |
| `subscription_no` | string | ❌ |

---

## شرح التعديل

### المشكلة
امتياز التأمين الطبي (`health_insurance`) كان بيرجع بيانات البوليصة بس (`medical_insurance`) من غير تفاصيل الاشتراكات الفعلية للموظف (مبلغ الاشتراك، أفراد العائلة، رقم الاشتراك، إلخ).

### الحل
1. **الـ Controller** بيعمل `fetchSubscriptionsByInsurance()` قبل الـ response — بيجيب كل اشتراكات المستخدم في Query واحدة ويخزنها indexed by `medical_insurance_id` (تجنباً لـ N+1).

2. **الـ Presenter** بيضيف حقل `subscriptions` فقط لما يكون نوع الامتياز `health_insurance` باستخدام `PrivilegeCardConfigService::TYPE_HEALTH_INSURANCE`.

3. **الـ create/update** بيستخدموا نفس السيرفس `MedicalInsuranceSubscriptionCRUDService::createMany()` اللي بيستخدمه `POST /medical-insurances/subscriptions` — بدون تكرار ولا duplication.

4. **الـ Validation** تم إضافته في `CreateUserPrivilegeRequest` و `UpdateUserPrivilegeRequest` بنفس قواعد الـ Medical Insurance Subscriptions الموجودة.

5. **إصلاح `company_id`**: تمت إضافة `Tenancy::initialize($companyId)` قبل استدعاء `createMany()` لأن راوتات `user_privileges` مش شغالة بميدلوير الـ tenancy وبالتالي `tenant('id')` كان بيرجع null.

### Acceptance Criteria

| المعيار | الحالة |
|---|---|
| `subscriptions` يظهر فقط مع نوع `health_insurance` | ✅ مشروط بـ `privilege.type === 'health_insurance'` |
| استخدام نفس سيرفس `MedicalInsuranceSubscriptionCRUDService` | ✅ `createMany()` و `list()` من نفس السيرفس |
| مفيش تكرار للـ business logic | ✅ صفر duplication |
| الـ response القديم ما يتغيرش للأنواع الأخرى | ✅ حقل `subscriptions` مش موجود أصلاً لغير health_insurance |
| أفراد العائلة يرجعوا زي ما بيرجعوا من سيرفس الاشتراكات | ✅ عبر `MedicalInsuranceSubscriptionPresenter` |
| الصلاحيات والفالداشن بتاعة الاشتراكات محترمة | ✅ القواعد منقولة من `CreateMedicalInsuranceSubscriptionRequest` |
| الأداء مقبول ومفيش N+1 | ✅ Batch fetch قبل الـ presenter loop |

### Tests

```
PASS  UserPrivilegeHealthInsuranceTest  (18 tests / 36 assertions)
PASS  MedicalInsuranceSubscriptionValidationTest  (13 tests / 25 assertions)
────────────────────────────────────────────────────────
Total: 31 passed / 0 failures
```
