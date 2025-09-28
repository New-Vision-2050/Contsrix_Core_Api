# NotificationSettings Module

## Overview

The NotificationSettings module provides a comprehensive notification system for CompanyOfficialDocuments. It allows configuration of email and SMS notifications based on document expiration dates and schedules automated reminders.

## Features

- **Flexible Notification Types**: Email, SMS, or both
- **Configurable Scheduling**: Daily or weekly reminders  
- **Multi-tenant Support**: Company and user-specific settings
- **Rich Email Templates**: Professional HTML templates with categorized documents
- **Command-line Testing**: Test mode for debugging without sending notifications
- **Automated Scheduling**: Integration with Laravel's task scheduler
- **Comprehensive Logging**: Detailed logs for monitoring and debugging

## Database Schema

### notification_settings Table

| Field | Type | Description |
|-------|------|-------------|
| id | UUID | Primary key |
| type | ENUM('mail', 'sms', 'both') | Notification delivery method |
| email | VARCHAR(255) | Email address (nullable) |
| phone | VARCHAR(20) | Phone number (nullable) |
| reminder_type | ENUM('daily', 'weekly') | Frequency of reminders |
| message | TEXT | Custom message (nullable) |
| is_active | BOOLEAN | Whether setting is active |
| company_id | UUID | Company association (nullable) |
| user_id | UUID | User association (nullable) |
| created_at | TIMESTAMP | Creation timestamp |
| updated_at | TIMESTAMP | Last update timestamp |

## API Endpoints

### Base URL: `/api/v1/notification_settings`

#### GET /
**List notification settings**
- **Permission**: `notification-settings.index`
- **Parameters**: 
  - `page` (optional): Page number
  - `per_page` (optional): Items per page
- **Response**: Paginated list of notification settings

#### POST /
**Create notification setting**
- **Permission**: `notification-settings.store`
- **Body**:
```json
{
    "type": "both|mail|sms",
    "email": "admin@company.com",
    "phone": "1234567890",
    "reminder_type": "daily|weekly",
    "message": "Optional custom message",
    "is_active": true
}
```

#### GET /{id}
**Get specific notification setting**
- **Permission**: `notification-settings.show`
- **Response**: Single notification setting details

#### PUT /{id}
**Update notification setting**
- **Permission**: `notification-settings.update`
- **Body**: Same as POST (all fields optional)

#### DELETE /{id}
**Delete notification setting**
- **Permission**: `notification-settings.destroy`

#### POST /{id}/toggle-status
**Toggle active status**
- **Permission**: `notification-settings.update`

#### GET /export
**Export notification settings**
- **Permission**: `notification-settings.export`
- **Response**: Excel file download

#### Filter Endpoints

#### GET /active
**Get active notification settings**
- **Permission**: `notification-settings.index`

#### GET /type/{type}
**Filter by notification type**
- **Permission**: `notification-settings.index`
- **Parameters**: `type` (mail, sms, both)

#### GET /reminder/{type}
**Filter by reminder frequency**
- **Permission**: `notification-settings.index`
- **Parameters**: `type` (daily, weekly)

#### GET /daily-reminders
**Get daily reminder settings**
- **Permission**: `notification-settings.index`

#### GET /weekly-reminders
**Get weekly reminder settings**
- **Permission**: `notification-settings.index`

## Laravel Commands

### Document Notification Command

#### Basic Usage
```bash
php artisan notifications:send-document-notifications
```

#### Options

##### Test Mode (Recommended for debugging)
```bash
php artisan notifications:send-document-notifications --test
```
- Shows what documents would trigger notifications
- Displays active notification settings
- **No actual notifications are sent**

##### Company-Specific Notifications
```bash
php artisan notifications:send-document-notifications --company=uuid-here
```

##### Custom Days Filter
```bash
php artisan notifications:send-document-notifications --days=7
```
- Send notifications for documents expiring within X days

##### Force Send (Override Time Restrictions)
```bash
php artisan notifications:send-document-notifications --force
```

## Scheduled Tasks

The system automatically runs notifications according to the following schedule:

### Daily Notifications
- **Time**: Every day at 9:00 AM (Asia/Riyadh timezone)
- **Purpose**: Check for expired and due-today documents
- **Log**: `storage/logs/document-notifications.log`

### Weekly Notifications  
- **Time**: Every Monday at 8:00 AM (Asia/Riyadh timezone)
- **Purpose**: Comprehensive weekly summary with force flag
- **Log**: `storage/logs/document-notifications-weekly.log`

## Setup Instructions

### 1. Database Migration
```bash
php artisan migrate --path=modules/NotificationSettings/Database/migrations
```

### 2. Seed Default Settings
```bash
php artisan module:seed NotificationSettings
```

### 3. Verify Installation
```bash
php artisan notifications:send-document-notifications --test
```

## Configuration

### Default Notification Settings
The seeder creates the following default configuration:
- **Type**: Both (email + SMS)
- **Email**: `admin@constrix-nv.com`
- **Phone**: `0542138116`
- **Frequency**: Weekly
- **Status**: Active

### Customization
Modify the seeder file to change default settings:
`modules/NotificationSettings/Database/seeders/DefaultNotificationSettingsSeeder.php`

## Email Template

The system uses a responsive HTML email template located at:
`modules/NotificationSettings/Resources/views/emails/document-expiration.blade.php`

### Template Features
- **Responsive Design**: Works on desktop and mobile
- **Document Categorization**: Expired, Due Today, Upcoming
- **Rich Styling**: Professional appearance with icons and colors
- **Custom Messages**: Support for additional custom content

## SMS Notifications

SMS messages are automatically formatted with:
- Document count summary
- Status indicators (❌ expired, ⏰ due today)
- Company and document type information
- Character limit optimization (480 chars max)

## Error Handling & Logging

### Log Locations
- **Command Execution**: `storage/logs/document-notifications.log`
- **Weekly Reports**: `storage/logs/document-notifications-weekly.log`
- **Laravel Logs**: `storage/logs/laravel.log`

### Common Issues

#### Database Field Mismatch
**Error**: `Column 'notify_date' not found`
**Solution**: Ensure you're using `notification_date` field in CompanyOfficialDocument model

#### No Notifications Sent
**Possible Causes**:
1. No active notification settings
2. No documents with `notification_date` <= today
3. Time restrictions (notifications only run 8 AM - 6 PM)

**Debug**: Use `--test` flag to diagnose

#### Permission Errors
**Solution**: Ensure proper permissions are configured in your system:
- `notification-settings.index`
- `notification-settings.store`  
- `notification-settings.show`
- `notification-settings.update`
- `notification-settings.destroy`
- `notification-settings.export`

## Integration Requirements

### CompanyOfficialDocument Model
Ensure your CompanyOfficialDocument model has:
- `notification_date` field (DATE type)
- Relationships: `company()`, `documentType()`

### Mail Configuration
Configure Laravel mail settings in `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

### SMS Configuration
Configure SMS provider in your notification channels.

## Production Checklist

- [ ] Run migrations
- [ ] Seed default settings
- [ ] Test command execution
- [ ] Configure mail settings
- [ ] Configure SMS provider
- [ ] Set up cron jobs for scheduler
- [ ] Monitor log files
- [ ] Test notification delivery
- [ ] Configure permissions

## Support

For issues or questions regarding the NotificationSettings module, check:
1. Log files for error details
2. Use `--test` mode for debugging
3. Verify database schema matches documentation
4. Ensure proper Laravel scheduler configuration

---

**Version**: 1.0  
**Last Updated**: September 28, 2025  
**Compatibility**: Laravel 10+, PHP 8.1+
