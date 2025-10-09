# File Sharing Feature - Quick Start Guide

## 🚀 Overview
This feature allows users to share files with other users via email notifications. The system automatically syncs shared users and sends personalized email notifications with a shareable link.

## 📋 Features
- ✅ Share files with multiple users at once
- ✅ Automatic email notifications to **newly added users only**
- ✅ No duplicate notifications for existing users
- ✅ Sync mechanism using `file_shares` table
- ✅ Personalized emails with file details
- ✅ Track who shared the file
- ✅ Count of new vs existing users
- ✅ Professional email template with action button

## 🔧 Quick Setup

### 1. Configure Email Settings
Add to your `.env` file:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourapp.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Run Migrations (if not already done)
```bash
php artisan migrate
```

### 3. Test the API
Use the included Postman collection at:
`modules/ArchiveLibrary/File/Postman/FileShare_API.postman_collection.json`

## 📡 API Endpoint

**Endpoint:** `POST /api/files/share`

**Headers:**
```
Authorization: Bearer {your_token}
Content-Type: application/json
X-Tenant: {your_tenant_id}
```

**Request Body:**
```json
{
    "file_id": "uuid-of-the-file",
    "user_ids": [
        "uuid-of-user-1",
        "uuid-of-user-2",
        "uuid-of-user-3"
    ]
}
```

**Success Response:**
```json
{
    "status": true,
    "message": "File shared successfully and notifications sent to new users",
    "data": {
        "file": {
            "id": "uuid",
            "name": "Document.pdf",
            "reference_number": "REF-001",
            "start_date": "2025-01-01",
            "end_date": "2025-12-31",
            "folder_id": "uuid",
            "access_type": "private"
        },
        "share_url": "https://yourapp.com/api/shared-files/{file_id}",
        "shared_with_count": 3,
        "new_users_count": 2,
        "existing_users_count": 1,
        "notifications_sent": 2
    }
}
```

**Note:** Only newly added users receive email notifications. If all users already have access, the message will be "File shared successfully (no new users to notify)" and `notifications_sent` will be 0.

## 📧 Email Notification

### Smart Notification Logic ⚡
The system uses intelligent logic to prevent duplicate notifications:
- ✅ **New users only**: Emails sent only to users being added for the first time
- ✅ **No duplicates**: Users who already have access receive no email
- ✅ **Transparent feedback**: Response shows exactly who was notified

**Example:** If a file is already shared with User A, and you share it again with Users A, B, C:
- User A: No email (already has access)
- Users B & C: Receive email notifications
- Response: `new_users_count: 2`, `existing_users_count: 1`, `notifications_sent: 2`

Recipients will receive an email containing:
- Personalized greeting with their name
- Name of the person who shared the file
- File details (name, reference number, dates)
- "View Shared File" action button with share URL
- Professional email template

📖 **See detailed examples:** `Docs/SMART_NOTIFICATION_LOGIC.md`

## 📂 Files Structure

```
modules/ArchiveLibrary/File/
├── Controllers/
│   └── FileController.php (shareFile method)
├── Repositories/
│   └── FileRepository.php (shareFile method)
├── Services/
│   └── FileCRUDService.php (shareFile method)
├── Requests/
│   └── ShareFileRequest.php
├── Notifications/
│   └── FileSharedNotification.php
├── Models/
│   ├── File.php (fileShare relationship)
│   └── FileShare.php
├── Postman/
│   └── FileShare_API.postman_collection.json
└── Docs/
    ├── FILE_SHARING.md (detailed documentation)
    ├── EMAIL_CONFIGURATION.md (email setup guide)
    └── README_FILE_SHARING.md (this file)
```

## 🔍 Database Schema

**file_shares table:**
```sql
- user_id: string (UUID)
- file_id: uuid
- created_at: timestamp
- updated_at: timestamp
```

The system uses Laravel's `sync()` method which:
- Adds new user associations
- Removes users not in the new list
- Updates existing associations

## ⚡ Performance Optimization

### Enable Queue System (Recommended)
```bash
# 1. Configure queue in .env
QUEUE_CONNECTION=database

# 2. Create queue table
php artisan queue:table
php artisan migrate

# 3. Start queue worker
php artisan queue:work
```

This sends emails asynchronously, improving API response time.

## 🧪 Testing

### Using Postman
1. Import the collection from `Postman/FileShare_API.postman_collection.json`
2. Configure environment variables
3. Send test requests

### Using cURL
```bash
curl -X POST "http://localhost:8000/api/files/share" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "X-Tenant: YOUR_TENANT_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "file_id": "9d2f3a1b-4c5d-6e7f-8a9b-0c1d2e3f4a5b",
    "user_ids": [
      "8c1e2d3b-4a5b-6c7d-8e9f-0a1b2c3d4e5f",
      "7b0c1d2e-3f4a-5b6c-7d8e-9f0a1b2c3d4e"
    ]
  }'
```

## 🐛 Troubleshooting

### Emails Not Sending?
1. Check `.env` mail configuration
2. Clear config cache: `php artisan config:clear`
3. Check logs: `storage/logs/laravel.log`
4. Verify queue worker is running (if using queues)

### Share Not Working?
1. Verify users exist in database
2. Check file_id is valid
3. Ensure authentication token is valid
4. Check tenant ID is correct

## 📚 Documentation

For detailed information, see:
- **FILE_SHARING.md** - Complete feature documentation
- **EMAIL_CONFIGURATION.md** - Email setup and configuration guide

## 🎯 Next Steps

1. **Configure production email service** (SendGrid, Mailgun, Amazon SES)
2. **Implement shared file access endpoint** using the generated share_url
3. **Add permission levels** (view, edit, download)
4. **Add expiration dates** for shared links
5. **Track share activity** for audit purposes

## 💡 Tips

- Use Mailtrap for development to avoid sending real emails
- Enable queue system for better performance
- Monitor email delivery rates in production
- Keep email templates branded and professional
- Consider adding unsubscribe options for notifications

## 🤝 Support

For issues or questions:
1. Check the documentation in `Docs/` folder
2. Review the Postman collection for examples
3. Check Laravel logs for error details
4. Verify email configuration is correct

---

**Created:** 2025-10-09
**Version:** 1.0
**Status:** Production Ready ✅
