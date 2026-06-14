# Health Insurance Field/Form Config — Implementation

## Summary

Added a code-driven, per-privilege-type field/form configuration so the frontend
knows which fields to render for each privilege type (especially `health_insurance`).
No database migrations or seeders were required — all data was already seeded.

---

## Files Changed

| File | Change |
|------|--------|
| `modules/Shared/Privilege/Support/PrivilegeFieldConfig.php` | **New.** Single source of truth for per-type field schema and validation rules. |
| `modules/Shared/Privilege/Presenters/PrivilegePresenter.php` | Added `fields` key sourced from `PrivilegeFieldConfig::forType()`. |
| `modules/UserInfo/UserPrivilege/Requests/CreateUserPrivilegeRequest.php` | Config-driven validation: looks up the privilege type from `privilege_id` and merges type-specific rules. |
| `modules/UserInfo/UserPrivilege/Requests/UpdateUserPrivilegeRequest.php` | Config-driven validation: resolves privilege type from the existing `UserPrivilege` record. |
| `modules/Shared/Privilege/Tests/Unit/PrivilegeFieldConfigTest.php` | **New.** 25 pure unit tests covering `forType`, labels, `required` flags, `validationRules`, and fallback behaviour. |
| `modules/Shared/Privilege/Tests/Unit/PrivilegePresenterTest.php` | **New.** 6 unit tests asserting that `fields` is present, correct, and backward-compatible. |

---

## Design

### `PrivilegeFieldConfig`

A `final` stateless class with two static methods:

```php
// Returns ordered field descriptors for the given privilege type.
PrivilegeFieldConfig::forType(string $type): array

// Returns Laravel validation rules derived from the same map.
PrivilegeFieldConfig::validationRules(string $type): array
```

Unknown types fall back to `'default'`, so adding a new privilege type requires
adding exactly one entry to the `MAP` constant — nothing else changes.

### Field descriptor shape

```json
{
  "name": "medical_insurance_id",
  "label": { "en": "Policy Number", "ar": "رقم البوليصة" },
  "input_type": "select",
  "options_source": "medical_insurances",
  "required": true
}
```

`input_type` values: `"text"`, `"textarea"`, `"select"`, `"number"`.
`options_source` is the FE lookup key (matches an existing API resource), or `null`
for free-text inputs.

### `health_insurance` specific fields

| Field | Label (ar) | input_type | options_source | required |
|-------|------------|------------|----------------|----------|
| `medical_insurance_id` | رقم البوليصة | select | medical_insurances | ✅ |
| `type_privilege_id` | نوع البدل | select | type_privileges | ✅ |
| `type_allowance_code` | نوع الاستحقاق | select | type_allowances | ✅ |
| `description` | وصف | textarea | — | ❌ |

TypePrivilege seeds: `فردي (Individual)` / `عائلي (Family)` — maps to the UI "allowance_kind".
TypeAllowance seeds: `ثابت (constant)` / `نسبة (percentage)` / `توفير (saving)` — maps to the UI "allowance_type".

---

## API Response Shape

The `fields` array is now included in every `Privilege` object. It appears in:
- `GET /api/v1/privileges` — catalog listing
- Any endpoint that nests `privilege` (e.g. `GET /api/v1/user_privileges/user/{id}`)

### Example — `GET /api/v1/privileges` (health_insurance entry)

```json
{
  "id": "uuid",
  "name": "تأمين طبي",
  "type": "health_insurance",
  "fields": [
    {
      "name": "medical_insurance_id",
      "label": { "en": "Policy Number", "ar": "رقم البوليصة" },
      "input_type": "select",
      "options_source": "medical_insurances",
      "required": true
    },
    {
      "name": "type_privilege_id",
      "label": { "en": "Allowance Kind", "ar": "نوع البدل" },
      "input_type": "select",
      "options_source": "type_privileges",
      "required": true
    },
    {
      "name": "type_allowance_code",
      "label": { "en": "Allowance Type", "ar": "نوع الاستحقاق" },
      "input_type": "select",
      "options_source": "type_allowances",
      "required": true
    },
    {
      "name": "description",
      "label": { "en": "Description", "ar": "وصف" },
      "input_type": "textarea",
      "options_source": null,
      "required": false
    }
  ]
}
```

---

## Validation Changes

### `POST /api/v1/user_privileges` (Create)

When `privilege_id` resolves to type `health_insurance`:
- `medical_insurance_id` → **required** | uuid | exists:medical_insurances,id
- `type_privilege_id` → **required** | string | exists:type_privileges,id
- `type_allowance_code` → **required** | string | exists:type_allowances,code
- `description` → nullable | string | max:2000

For all other types, the DEFAULT config applies (type_privilege_id and
type_allowance_code required; charge_amount, period_id, description nullable).

### `POST /api/v1/user_privileges/{id}` (Update)

Same rules derived from the privilege type of the existing record.

---

## Adding Future Privilege Types

Add one entry to `PrivilegeFieldConfig::MAP`:

```php
'new_type' => [
    ['name' => 'some_field', 'label' => ['en' => '...', 'ar' => '...'],
     'input_type' => 'text', 'options_source' => null, 'required' => true],
    // ...
],
```

No other files need to change.
