# Frontend Changes Required – Procedure Step Card

> Backend change: added `notify_by_push` and Twilio WhatsApp delivery to procedure setting steps.
> Date: 2026-06-27

---

## 1. Summary

The procedure step card / form needs two new notification toggles:

- **Push Notification** (`notify_by_push`) – FCM to the mobile app.
- **WhatsApp** (`notify_by_whatsapp`) – Twilio WhatsApp message to the user's phone.

Both are sent only when the step is configured for them and the user has a valid device token or phone number.

---

## 2. Step card UI changes

Add two new toggles/checkboxes in the procedure step card, grouped with the existing notification toggles:

| Existing | New |
|---|---|
| Email (`notify_by_email`) | |
| WhatsApp (`notify_by_whatsapp`) | ✅ already present, now actually delivered |
| SMS (`notify_by_sms`) | |
| | **Push Notification** (`notify_by_push`) ⭐ |

Suggested labels:

- `Push Notification` / `إشعار التطبيق`
- `WhatsApp` / `واتساب` (already exists)

### Recommended field order

1. Notify by Email
2. Notify by WhatsApp
3. Notify by SMS
4. **Notify by Push** ⭐
5. Skipping Period
6. Escalation Management Hierarchy

---

## 3. API request / response contract

### Create / update payload

```json
{
  "notify_by_email": true,
  "notify_by_whatsapp": true,
  "notify_by_sms": false,
  "notify_by_push": true
}
```

Validation rules:

- `notify_by_push`: create `nullable|boolean`, update `sometimes|boolean`.
- `notify_by_whatsapp`: create `nullable|boolean`, update `sometimes|boolean`.

Both default to `false` when omitted.

### Step response (presenter)

```json
{
  "notify_by_email": true,
  "notify_by_whatsapp": true,
  "notify_by_sms": false,
  "notify_by_push": true
}
```

Use these values to set the initial toggle states when editing a step.

---

## 4. Example full step payload

### Create request

```json
{
  "procedure_setting_id": "123",
  "name": "Manager Approval",
  "step_order": 1,
  "action_taker_type": "management_hierarchy",
  "action_taker_management_hierarchy_type": "branch_manager",
  "notify_by_email": true,
  "notify_by_whatsapp": true,
  "notify_by_sms": false,
  "notify_by_push": true,
  "skipping_period": 0
}
```

### Update request

```json
{
  "name": "Manager Approval",
  "notify_by_whatsapp": true,
  "notify_by_push": true
}
```

---

## 5. No new endpoints from the step card

- The existing create/update step endpoints handle both new flags.
- No new UI flow is needed beyond the two toggles.

---

## 6. Admin / DevOps configuration (for reference)

The backend will deliver the actual messages, but the platform needs the following credentials. These are configured in **Settings > Drivers** via the existing driver API.

### WhatsApp driver fields

`PUT /api/v1/settings/driver/{id}`

```json
{
  "config": {
    "TWILIO_SID": "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "TWILIO_AUTH_TOKEN": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "TWILIO_WHATSAPP_FROM": "whatsapp:+14155238886"
  }
}
```

`TWILIO_WHATSAPP_FROM` must include the `whatsapp:` prefix (e.g. `whatsapp:+14155238886`).

### Push notification requirements

- Mobile app registers FCM tokens via `POST /api/v1/user/fcm-token`.
- A migration flushed all existing FCM tokens; users must re-register after deployment.

---

## 7. Migrations

- `2026_06_27_145500_add_notify_by_push_to_procedure_setting_steps.php` adds `notify_by_push`.

Run this migration before testing.
