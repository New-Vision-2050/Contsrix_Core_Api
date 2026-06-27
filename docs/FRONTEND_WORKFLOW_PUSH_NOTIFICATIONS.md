# Frontend Changes Required – Procedure Step Card

> Backend change: added `notify_by_push` flag to procedure setting steps.
> Date: 2026-06-27

---

## 1. Summary

The procedure step card / form now needs a new toggle to control **push notifications** (FCM) for action takers.

When enabled, the backend sends an FCM push notification to every resolved action-taker user who has a valid `fcm_token`.

---

## 2. New field: `notify_by_push`

### UI change

Add a new toggle/checkbox in the procedure step card, alongside the existing notification toggles:

- **Email** (`notify_by_email`)
- **WhatsApp** (`notify_by_whatsapp`)
- **SMS** (`notify_by_sms`)
- **Push Notification** (`notify_by_push`) ⭐ **NEW**

Suggested labels:

- EN: `Push Notification`
- AR: `إشعار التطبيق`

### API contract – send (create/update)

Include the new boolean in the step payload:

```json
{
  "notify_by_email": true,
  "notify_by_whatsapp": false,
  "notify_by_sms": false,
  "notify_by_push": true
}
```

Validation rules:

- **Create**: `notify_by_push` is `nullable|boolean` (defaults to `false` when omitted).
- **Update**: `notify_by_push` is `sometimes|boolean`.

### API contract – receive (step response)

The presenter now returns the flag:

```json
{
  "notify_by_email": true,
  "notify_by_whatsapp": false,
  "notify_by_sms": false,
  "notify_by_push": true
}
```

Use this value to set the initial state of the toggle when editing a step.

---

## 3. Step card placement

Place the new toggle immediately after the SMS toggle, before the `skipping_period` / escalation fields.

Example order:

1. Notify by Email
2. Notify by WhatsApp
3. Notify by SMS
4. **Notify by Push** ⭐
5. Skipping Period
6. Escalation Management Hierarchy

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
  "notify_by_whatsapp": false,
  "notify_by_sms": false,
  "notify_by_push": true,
  "skipping_period": 0
}
```

### Update request

```json
{
  "name": "Manager Approval",
  "notify_by_push": true
}
```

---

## 5. No other frontend changes required

- No new endpoint needs to be called from the step card.
- No new UI flow is needed.
- The existing create/update step endpoints handle the new flag.

---

## 6. Mobile / FCM notes (for reference)

The backend will handle the actual push delivery. Mobile requirements stay the same as before:

- Mobile app must register/update FCM tokens via `POST /api/v1/user/fcm-token`.
- A migration was added to flush all existing FCM tokens; users will need to re-register after deployment.

---

## 7. Migration

- `2026_06_27_145500_add_notify_by_push_to_procedure_setting_steps.php` adds the column.

Run this migration before testing.
