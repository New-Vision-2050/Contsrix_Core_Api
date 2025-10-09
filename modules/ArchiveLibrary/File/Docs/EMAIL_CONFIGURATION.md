# Email Configuration for File Sharing

## Overview
The file sharing feature sends email notifications to users when a file is shared with them. This document explains how to configure email settings for the application.

## Laravel Mail Configuration

### Step 1: Configure .env File

Add or update the following mail configuration in your `.env` file:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Step 2: Choose Your Mail Provider

#### Option 1: Mailtrap (Development/Testing)
Perfect for testing emails without sending real emails:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
```

#### Option 2: Gmail (Production - Not Recommended)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
```
**Note:** You need to create an App Password in your Google Account settings.

#### Option 3: SendGrid (Recommended for Production)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your_sendgrid_api_key
MAIL_ENCRYPTION=tls
```

#### Option 4: Amazon SES (Production)
```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
```

#### Option 5: Mailgun (Production)
```env
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=your_domain.mailgun.org
MAILGUN_SECRET=your_mailgun_secret
MAILGUN_ENDPOINT=api.mailgun.net
```

## Queue Configuration (Optional but Recommended)

To send emails asynchronously and improve performance:

### Step 1: Configure Queue Driver
```env
QUEUE_CONNECTION=database
```

### Step 2: Run Migrations
```bash
php artisan queue:table
php artisan migrate
```

### Step 3: Start Queue Worker
```bash
php artisan queue:work
```

Or for development:
```bash
php artisan queue:listen
```

## Email Template Customization

The email template can be customized by modifying the notification class:

**Location:** `modules/ArchiveLibrary/File/Notifications/FileSharedNotification.php`

### Customize Email Content
```php
public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Custom Subject Here')
        ->greeting('Custom Greeting!')
        ->line('Your custom message here')
        ->action('Custom Button Text', $this->shareUrl)
        ->line('Custom footer text');
}
```

### Customize Email Theme
You can publish Laravel's mail templates:
```bash
php artisan vendor:publish --tag=laravel-mail
```

Then customize the templates in `resources/views/vendor/mail/`.

## Testing Email Configuration

### Test with Tinker
```bash
php artisan tinker
```

```php
use Illuminate\Support\Facades\Mail;
use Modules\User\Models\User;

$user = User::first();
Mail::raw('Test email', function ($message) use ($user) {
    $message->to($user->email)
            ->subject('Test Email');
});
```

### Test File Sharing Email
Make a POST request to the share endpoint:
```bash
curl -X POST "http://localhost:8000/api/files/share" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "file-uuid",
    "user_ids": ["user-uuid"]
  }'
```

## Troubleshooting

### Emails Not Sending

1. **Check Mail Configuration:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Check Laravel Logs:**
   ```
   storage/logs/laravel.log
   ```

3. **Test SMTP Connection:**
   ```bash
   telnet smtp.mailtrap.io 2525
   ```

4. **Verify Queue is Running:**
   If using queues, make sure the queue worker is active:
   ```bash
   php artisan queue:work
   ```

### Common Issues

**Issue:** "Connection refused"
- **Solution:** Check firewall settings and SMTP port

**Issue:** "Authentication failed"
- **Solution:** Verify username/password in .env file

**Issue:** "TLS/SSL error"
- **Solution:** Try changing `MAIL_ENCRYPTION` to `ssl` or `tls`

**Issue:** Emails go to spam
- **Solution:** Use a reputable email service provider and configure SPF/DKIM records

## Email Notification Features

The file sharing notification includes:
- ✅ Personalized greeting with recipient's name
- ✅ Name of the person who shared the file
- ✅ Complete file details (name, reference number, dates)
- ✅ Direct action button to view the file
- ✅ Professional Laravel email template
- ✅ Support for queueing (async sending)

## Production Recommendations

1. **Use a dedicated email service** (SendGrid, Mailgun, Amazon SES)
2. **Enable queue system** for better performance
3. **Configure SPF and DKIM records** for better deliverability
4. **Monitor email delivery rates** and bounces
5. **Set up proper error handling** and logging
6. **Use environment-specific configurations**

## Security Best Practices

1. **Never commit .env files** to version control
2. **Use app passwords** instead of account passwords
3. **Rotate API keys regularly**
4. **Enable 2FA** on email service accounts
5. **Monitor for suspicious activity**
6. **Use encrypted connections** (TLS/SSL)
