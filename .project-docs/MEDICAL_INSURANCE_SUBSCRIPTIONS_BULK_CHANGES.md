# Medical Insurance Subscriptions — API Changes

## Summary

Three changes were made to the subscriptions API:

1. **Create** and **Update** are now bulk operations — they accept and return arrays of subscriptions.
2. The **Update** endpoint URL no longer includes a subscription `{id}`.
3. The **List** endpoint now accepts `user_ids[]` to filter subscriptions for multiple users in one request.

---

## Endpoint Changes

| Action | Before | After |
|--------|--------|-------|
| Create | `POST /api/v1/medical-insurances/subscriptions` — single object body | `POST /api/v1/medical-insurances/subscriptions` — array wrapped in `{ "subscriptions": [...] }` |
| Update | `PUT /api/v1/medical-insurances/subscriptions/{id}` — single object body | `PUT /api/v1/medical-insurances/subscriptions` — array wrapped in `{ "subscriptions": [...] }` (no `{id}` in URL) |
| List | `GET /api/v1/medical-insurances/subscriptions?user_id=<uuid>` — single user only | `GET /api/v1/medical-insurances/subscriptions?user_ids[]=<uuid1>&user_ids[]=<uuid2>` — supports multiple users |
| Get one | `GET /api/v1/medical-insurances/subscriptions/{id}` | **Unchanged** |
| Delete | `DELETE /api/v1/medical-insurances/subscriptions/{id}` | **Unchanged** |

---

## Request Body

Both `POST` and `PUT` now share the same body shape:

```json
{
  "subscriptions": [
    {
      "user_id": "uuid",
      "medical_insurance_id": "uuid",
      "medical_insurance_category_id": "uuid or null",
      "amount": 3000,
      "subscription_no": "SUB-04",
      "status": 1,
      "family_members": [
        {
          "name": "فاطمة أحمد",
          "national_id": "1234567890",
          "relation": "زوجة",
          "amount": 1500,
          "subscription_no": "SUB-001-F1"
        },
        {
          "name": "محمد أحمد",
          "national_id": "9876543210",
          "relation": "ابن",
          "amount": 800,
          "subscription_no": null
        }
      ]
    }
  ]
}
```

You may send multiple objects inside `subscriptions[]` in a single request.

### Field reference

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| `subscriptions` | array | Yes | Min 1 item |
| `subscriptions.*.user_id` | UUID string | Yes | Must exist in `users` |
| `subscriptions.*.medical_insurance_id` | UUID string | Yes | Must exist in `medical_insurances` |
| `subscriptions.*.medical_insurance_category_id` | UUID string | No | Must exist in `medical_insurance_categories` if provided |
| `subscriptions.*.amount` | number | Yes | ≥ 0 |
| `subscriptions.*.subscription_no` | string | Yes | Max 255 chars. Must be unique across the whole batch (`distinct`). On **Create** also unique in the database. On **Update** no DB uniqueness check (existing rows are deleted first). |
| `subscriptions.*.status` | integer | No | `-1`, `0`, or `1`. Defaults to `1` if omitted. |
| `subscriptions.*.family_members` | array | No | Can be empty or omitted |
| `subscriptions.*.family_members.*.name` | string | Yes (if family_members present) | Max 255 |
| `subscriptions.*.family_members.*.national_id` | string | Yes (if family_members present) | Max 50 |
| `subscriptions.*.family_members.*.relation` | string | Yes (if family_members present) | Max 100 |
| `subscriptions.*.family_members.*.amount` | number | Yes (if family_members present) | ≥ 0 |
| `subscriptions.*.family_members.*.subscription_no` | string | No | Nullable, no uniqueness enforced |

---

## Create & Update Behavior (Replace-and-Insert)

Both `POST` and `PUT` enforce **one subscription per `(user_id, medical_insurance_id)` pair**. The flow is identical for both:

1. For every distinct `(user_id, medical_insurance_id)` pair found in the submitted `subscriptions[]`, **all existing subscriptions** (and their family members) for that pair are soft-deleted.
2. The submitted subscriptions are then inserted fresh with new IDs.

Everything runs in a single DB transaction.

**Practical implication:** always send the complete desired state for each `(user_id, medical_insurance_id)` pair. Any existing subscriptions for that pair not included in the payload will be deleted.

---

## Response Shape

Both `POST` and `PUT` return the same shape — an array of the created/replaced subscriptions:

```json
{
  "code": "SUCCESS_WITH_LIST_PAYLOAD_OBJECTS",
  "message": null,
  "payload": [
    {
      "id": "uuid",
      "user_id": "uuid",
      "medical_insurance_id": "uuid",
      "medical_insurance_category_id": "uuid or null",
      "amount": "3000.00",
      "subscription_no": "SUB-04",
      "status": 1,
      "user": {
        "id": "uuid",
        "name": "Ahmed Ali"
      },
      "medical_insurance": {
        "id": "uuid",
        "name": "Insurance Name",
        "policy_number": "POL-001"
      },
      "medical_insurance_category": {
        "id": "uuid",
        "name": "Category Name",
        "type": "...",
        "coverage_limit": "...",
        "description": "..."
      },
      "family_members": [
        {
          "id": "uuid",
          "name": "فاطمة أحمد",
          "national_id": "1234567890",
          "relation": "زوجة",
          "amount": "1500.00",
          "subscription_no": "SUB-001-F1",
          "created_at": "2026-05-19 18:00:00",
          "updated_at": "2026-05-19 18:00:00"
        }
      ],
      "created_at": "2026-05-19 18:00:00",
      "updated_at": "2026-05-19 18:00:00"
    }
  ]
}
```

---

## Validation Error Shape

Laravel returns a `422` with the standard error body. Field paths use dot notation with the array index, for example:

```json
{
  "message": "The subscriptions.0.subscription_no has already been taken.",
  "errors": {
    "subscriptions.0.subscription_no": [
      "The subscriptions.0.subscription_no has already been taken."
    ]
  }
}
```

---

## Get Subscriptions by Multiple Users

Use the `user_ids[]` query parameter on the list endpoint to fetch subscriptions for several users at once.

**Request:**

```
GET /api/v1/medical-insurances/subscriptions?user_ids[]=<uuid1>&user_ids[]=<uuid2>
```

Can be combined with any other filter:

```
GET /api/v1/medical-insurances/subscriptions?user_ids[]=<uuid1>&user_ids[]=<uuid2>&status=1&page=1&per_page=20
```

**Query parameter reference (updated):**

| Parameter | Type | Required | Notes |
|-----------|------|----------|-------|
| `page` | integer | No | Default 1 |
| `per_page` | integer | No | Default 10 |
| `user_id` | UUID string | No | Filter by a single user |
| `user_ids[]` | UUID string (repeat) | No | Filter by multiple users. Each value must exist in `users`. |
| `medical_insurance_id` | UUID string | No | Filter by insurance |
| `medical_insurance_category_id` | UUID string | No | Filter by category |
| `status` | integer | No | `-1`, `0`, or `1` |

**Response shape:** same paginated list as the existing GET endpoint.

---

## Migration Notes for the Frontend

1. **Wrap the body**: Instead of sending a flat object, wrap all subscriptions inside a `"subscriptions"` key as an array.
2. **Remove `{id}` from the PUT URL**: The update endpoint is now `PUT /api/v1/medical-insurances/subscriptions` — no subscription ID in the path.
3. **Expect an array response**: Both create and update now return `payload` as an array, even when you submit a single subscription.
4. **Both POST and PUT replace existing data**: One subscription per `(user_id, medical_insurance_id)` is enforced. On both create and update, existing subscriptions for a pair are deleted before the new ones are inserted. Always send the full desired state for each pair.
5. **Single-item operations still work**: You can send `"subscriptions": [{ ... one item ... }]` for single creates/updates.
6. **Fetch subscriptions for multiple users**: Use `user_ids[]=<uuid1>&user_ids[]=<uuid2>` instead of making one request per user.
