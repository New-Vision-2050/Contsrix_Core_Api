# Voice Notification Frontend Guide

## What changed

Voice calls are now a first-class notification channel in the workflow step card, alongside Email, SMS, WhatsApp, and Push.

When a workflow step has `notify_by_voice: true`, the backend will call the action taker's phone number using Twilio.

## Backend changes

### 1. New field on procedure setting steps

The `procedure_setting_steps` table and API responses now include:

```json
{
  "notify_by_email": true,
  "notify_by_whatsapp": false,
  "notify_by_sms": false,
  "notify_by_push": false,
  "notify_by_voice": true
}
```

### 2. API endpoints affected

Create and Update step endpoints now accept `notify_by_voice` as a boolean.

- `POST /api/procedure-settings/{procedureSettingId}/steps`
- `PUT /api/procedure-settings/{procedureSettingId}/steps/{stepId}`

### 3. Presenter output

`ProcedureSettingStepPresenter` now returns `notify_by_voice` in step objects.

## Frontend changes required

### 1. Step card UI

Add a **Voice** toggle/checkbox next to the existing Email, SMS, WhatsApp, and Push toggles.

```
[ ] Email  [ ] SMS  [ ] WhatsApp  [ ] Push  [ ] Voice
```

### 2. Form payload

When creating or updating a step, include `notify_by_voice`:

```json
{
  "name": "Manager approval",
  "notify_by_email": true,
  "notify_by_sms": false,
  "notify_by_whatsapp": false,
  "notify_by_push": false,
  "notify_by_voice": true
}
```

### 3. Display existing steps

When rendering a step from the API, show the Voice notification state:

```tsx
const channels = [
  { key: 'notify_by_email', label: 'Email' },
  { key: 'notify_by_sms', label: 'SMS' },
  { key: 'notify_by_whatsapp', label: 'WhatsApp' },
  { key: 'notify_by_push', label: 'Push' },
  { key: 'notify_by_voice', label: 'Voice' },
];
```

### 4. Icons (optional)

You can use a phone/call icon for the Voice channel, e.g.:

- Lucide: `Phone` or `PhoneCall`
- Material: `phone` or `call`

## How voice calls work

1. The user receives a Twilio voice call on their phone.
2. The call reads the configured TwiML message (currently Arabic: "مرحباً، أهلاً بك في كونستريكس").
3. The backend requires the user to have a `phone` and `phone_code`.
4. Voice calls are sent using Twilio API keys configured separately from WhatsApp.

## Environment configuration

The backend team must configure these GitHub secrets / env variables:

- `TWILIO_VOICE_API_KEY_SID`
- `TWILIO_VOICE_API_KEY_SECRET`
- `TWILIO_VOICE_FROM`

No frontend code changes are required for Twilio configuration.

## Testing

1. Enable `notify_by_voice` on a step.
2. Trigger a workflow that reaches that step.
3. The action taker should receive a voice call.
4. Check Twilio logs if the call does not arrive.

## Notes

- Voice is a separate channel from WhatsApp. The number used for voice may not support WhatsApp.
- Voice calls require a valid phone number with country code.
- Emergency address may be required by Twilio for US/Canada outbound calls.
