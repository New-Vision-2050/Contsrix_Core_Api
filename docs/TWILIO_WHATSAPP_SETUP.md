# Twilio WhatsApp Setup & Operations Guide

> For workflow action-taker notifications via `notify_by_whatsapp`.
> Date: 2026-06-27

---

## 1. What the company needs to provide

To send WhatsApp messages from the application, the company needs:

1. **A Twilio account** with a verified project.
2. **A Twilio phone number** approved for WhatsApp messaging (this is the **company number** that sends the messages).
3. **Twilio Account SID** and **Auth Token**.
4. A **WhatsApp Business Profile** (optional but recommended).

---

## 2. Twilio account setup

### 2.1 Create / sign in to Twilio

- Go to https://www.twilio.com/try-twilio and create an account.
- Verify the account with email and phone number.

### 2.2 Get Account SID and Auth Token

- In the Twilio Console, open the **Account Info** section.
- Copy:
  - **Account SID** (starts with `AC...`)
  - **Auth Token** (click to reveal)

These two values will be entered into the application settings.

### 2.3 Get a Twilio phone number

- Go to **Phone Numbers > Manage > Buy a number**.
- Choose a number that supports **WhatsApp**.
- In most countries, Twilio numbers must be enabled for WhatsApp separately; follow the Twilio onboarding for your country.

### 2.4 Enable the number for WhatsApp

- Twilio has two modes for WhatsApp:

| Mode | Use case | From address format |
|---|---|---|
| **Twilio Sandbox** | Testing only | `whatsapp:+14155238886` (Twilio's sandbox number) |
| **Your own Twilio number** | Production | `whatsapp:+<your-twilio-number>` |

- For production, submit the number for WhatsApp Sender approval in the Twilio Console.
- Wait for Twilio's approval before sending production messages.

---

## 3. Backend configuration

### 3.1 Environment variables (recommended for local / single-tenant)

Add these to `.env`:

```env
TWILIO_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_WHATSAPP_FROM=whatsapp:+14155238886
```

`TWILIO_WHATSAPP_FROM` must include the `whatsapp:` prefix.

### 3.2 Per-company driver configuration (recommended for multi-tenant)

The application stores drivers in the `drivers` table per company. After running seeders, a `whatsapp` / `twilio` driver is created. Use the existing driver API to update it:

```http
PUT /api/v1/settings/driver/{twilio-whatsapp-driver-id}
Content-Type: application/json

{
  "config": {
    "TWILIO_SID": "ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "TWILIO_AUTH_TOKEN": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx",
    "TWILIO_WHATSAPP_FROM": "whatsapp:+14155238886"
  }
}
```

This lets each company use its own Twilio number.

### 3.3 Run seeders

If the `twilio` driver was not created yet, run the setting module seeder:

```bash
php artisan db:seed --class=Modules\Setting\Database\Seeders\DriverTableSeeder
```

For tenants, run the tenant-specific seeder.

---

## 4. Company WhatsApp number requirements

- The number must be able to receive SMS or voice calls for verification (Twilio requires this).
- The number must be enabled for WhatsApp by Twilio.
- The number should not be already registered as a personal WhatsApp account if you want to use it as a business sender. Twilio may require you to delete the personal WhatsApp registration first.
- In some countries, a **WhatsApp Business Account (WABA)** and **Meta Business Manager** verification are required for production. Plan for 1–5 business days for approval.

---

## 5. Install the Twilio SDK

The dependency is already added to `composer.json`:

```json
"twilio/sdk": "^8.3"
```

Run:

```bash
composer install
```

If the application is running in Docker or Octane, rebuild the container image after this change.

---

## 6. DevOps / deployment notes

### 6.1 Add secrets to GitHub Actions

The CI/CD pipeline (`@/Users/amrmohamed/Documents/projects/constrix/.github/workflows/ci_cd.yml`) passes secrets to the server via `appleboy/ssh-action`. Add these repository secrets in **GitHub > Settings > Secrets and variables > Actions**:

- `TWILIO_SID`
- `TWILIO_AUTH_TOKEN`
- `TWILIO_WHATSAPP_FROM`

The workflow already forwards them to the server and exports them before running `@/Users/amrmohamed/Documents/projects/constrix/devops/deploy.sh`. The deploy script then writes them into the generated `.env` file used by the container.

Do **not** commit them to the repository. Only `.env.example` contains empty placeholders.

### 6.2 Docker / Octane

If using Laravel Octane or Docker:

1. Rebuild the image after `composer install`.
2. Inject the Twilio env vars at runtime.
3. Restart the Octane workers / containers.

### 6.3 Queue workers

WhatsApp messages are sent synchronously inside the request lifecycle. If traffic grows, consider dispatching the `WorkflowActionRequired` notification to a queue by setting `public $queue` or using `ShouldQueue`. No queue changes are required for the initial integration.

---

## 7. Testing

### 7.1 Sandbox testing (recommended first step)

1. Keep `TWILIO_WHATSAPP_FROM=whatsapp:+14155238886` (Twilio sandbox number).
2. Join the sandbox from the recipient phone by sending the sandbox join code shown in the Twilio Console.
3. Create a procedure step with `notify_by_whatsapp: true`.
4. Trigger the workflow so the recipient becomes an action taker.
5. Confirm the message arrives.

### 7.2 Production testing

1. Replace `TWILIO_WHATSAPP_FROM` with your own approved Twilio number.
2. The recipient must have WhatsApp installed and the number must be saved in their contacts (recommended, not strictly required).
3. Verify the message is received.

### 7.3 What to check if it fails

- Check Laravel logs for `Twilio WhatsApp message failed`.
- Verify `TWILIO_WHATSAPP_FROM` includes `whatsapp:`.
- Verify the recipient's phone number includes country code.
- Verify the Twilio number is approved for WhatsApp.
- Check Twilio Console > Messaging Insights for error codes.

---

## 8. Compliance & content policies

- WhatsApp has strict anti-spam and opt-in policies. Use WhatsApp notifications only for workflow actions where the user is already an action taker.
- Follow Twilio's Acceptable Use Policy and WhatsApp Commerce Policy.
- Twilio charges per WhatsApp conversation (24-hour session window). Template messages are required for outbound messages outside a conversation window.
- For high-volume production, you may need to register **message templates** with WhatsApp and use Twilio's template API instead of plain text. The current implementation sends plain text; it works inside a conversation window or with sandbox testing.

---

## 9. Quick checklist

- [ ] Twilio account created and verified.
- [ ] Twilio phone number purchased and enabled for WhatsApp.
- [ ] Account SID and Auth Token copied into `.env` or driver config.
- [ ] `TWILIO_WHATSAPP_FROM` includes `whatsapp:` prefix.
- [ ] `composer install` ran to install `twilio/sdk`.
- [ ] Seeder ran to create the `twilio` WhatsApp driver.
- [ ] Procedure step created with `notify_by_whatsapp: true`.
- [ ] Test message received on recipient phone.
- [ ] (Production) WhatsApp Sender approval completed in Twilio.
