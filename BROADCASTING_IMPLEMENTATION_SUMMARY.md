# Real-Time Broadcasting Implementation Summary

## Overview
Complete implementation of Laravel Reverb broadcasting for real-time notifications across:
1. **Attachment Requests** - Project file sharing notifications
2. **Resource Shares** - Project/resource sharing notifications  
3. **Unified Notification API** - Centralized pending counts

---

## 📋 Task Completion Status

### ✅ Phase 1: Attachment Request Broadcasting

**Files Created:**
- `config/broadcasting.php` - Laravel Reverb configuration
- `modules/Project/ProjectManagement/Events/AttachmentRequestCreated.php`
- `modules/Project/ProjectManagement/Events/AttachmentRequestResponded.php`

**Files Modified:**
- `modules/Project/ProjectManagement/Services/AttachmentRequestService.php`
  - Added event broadcasting on create/approve/decline
  - Added `broadcastToReceiverCompany()` and `broadcastToSenderCompany()` methods

**Functionality:**
- ✅ Broadcasts to receiver company users when new attachment request created
- ✅ Broadcasts to sender company users when request approved/declined
- ✅ Channel: `inbox.{user_id}`
- ✅ Events: `attachment-request.created`, `attachment-request.responded`

---

### ✅ Phase 2: Resource Share Broadcasting

**Files Created:**
- `modules/Shared/ResourceShare/Events/ResourceShared.php`
- `modules/Shared/ResourceShare/Events/ResourceShareResponded.php`

**Files Modified:**
- `modules/Shared/ResourceShare/Services/ResourceShareService.php`
  - Added event broadcasting on share/accept/reject
  - Added `broadcastToSharedCompany()` and `broadcastToOwnerCompany()` methods

**Functionality:**
- ✅ Broadcasts to shared company users when resource is shared
- ✅ Broadcasts to owner company users when share accepted/rejected
- ✅ Channel: `inbox.{user_id}`
- ✅ Events: `resource.shared`, `resource.share-responded`

---

### ✅ Phase 3: Unified Notification API

**Files Created:**
- `modules/Shared/Notification/Services/NotificationCountService.php`
- `modules/Shared/Notification/Controllers/NotificationController.php`
- `modules/Shared/Notification/Routes/api.php`

**API Endpoints:**

#### 1. GET `/api/notifications/pending-counts`
Returns pending notification counts

**Response:**
```json
{
  "success": true,
  "data": {
    "total_pending": 12,
    "pending_attachment_requests": 5,
    "semi_approved_attachment_requests": 3,
    "pending_resource_shares": 4,
    "breakdown": {
      "attachment_requests": {
        "pending": 5,
        "semi_approved": 3,
        "total": 8
      },
      "resource_shares": {
        "pending": 4
      }
    }
  }
}
```

#### 2. GET `/api/notifications/pending`
Returns detailed pending notifications (last 10 of each type)

**Response:**
```json
{
  "success": true,
  "data": {
    "attachment_requests": [
      {
        "id": "uuid",
        "type": "attachment_request",
        "serial_number": "ATR-20260412-0001",
        "name": "Request Name",
        "status": "pending",
        "sender_company": "Company A",
        "project": "Project X",
        "created_by": "John Doe",
        "created_at": "2026-04-12T11:00:00.000000Z"
      }
    ],
    "resource_shares": [
      {
        "id": "uuid",
        "type": "resource_share",
        "shareable_type": "App\\Models\\Project",
        "resource_name": "Project X",
        "status": "pending",
        "owner_company": "Company B",
        "shared_by": "Jane Smith",
        "notes": "Please review",
        "created_at": "2026-04-12T10:00:00.000000Z"
      }
    ],
    "total_count": 15
  }
}
```

---

## 🎯 Broadcasting Events Summary

| Event | Channel | Triggered When | Sent To | Data |
|-------|---------|----------------|---------|------|
| `AttachmentRequestCreated` | `inbox.{user_id}` | New attachment request created | Receiver company users | Request details, sender info, item count |
| `AttachmentRequestResponded` | `inbox.{user_id}` | Request approved/declined | Sender company users | Response action, responder info |
| `ResourceShared` | `inbox.{user_id}` | Resource shared with company | Shared-with company users | Resource details, owner info |
| `ResourceShareResponded` | `inbox.{user_id}` | Share accepted/rejected | Owner company users | Response action, responder info |

---

## 🔧 Installation & Setup

### 1. Install Laravel Reverb

```bash
# Install broadcasting with Reverb
php artisan install:broadcasting

# If package not found:
composer require laravel/reverb
php artisan install:broadcasting
```

### 2. Configure Environment

Update `.env`:
```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=123456
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 3. Start Services

```bash
# Terminal 1: Start Reverb WebSocket server
php artisan reverb:start --debug

# Terminal 2: Start queue worker (important!)
php artisan queue:work --verbose

# Terminal 3: Start Laravel app
php artisan serve
```

---

## 📱 React Frontend Integration

### Install Dependencies

```bash
npm install laravel-echo pusher-js
```

### Create Echo Client

**File:** `src/utils/echo.js`

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
    },
});

export default echo;
```

### Notification Component Example

```javascript
import { useEffect, useState } from 'react';
import echo from './utils/echo';
import axios from 'axios';

function NotificationBadge() {
    const [pendingCount, setPendingCount] = useState(0);
    const userId = localStorage.getItem('user_id');

    // Fetch initial count
    useEffect(() => {
        axios.get('/api/notifications/pending-counts')
            .then(res => setPendingCount(res.data.data.total_pending));
    }, []);

    // Listen to real-time events
    useEffect(() => {
        // 1. Listen to User Inbox (For Attachment Requests)
        const inboxChannel = echo.private(`inbox.${userId}`);

        inboxChannel.listen('.attachment-request.created', (event) => {
            setPendingCount(prev => prev + 1);
            showToast(`New attachment request: ${event.name}`);
        });

        inboxChannel.listen('.attachment-request.responded', (event) => {
            showToast(`Request ${event.action}: ${event.name}`);
        });

        // 2. Listen to Company Channel (For Resource Shares)
        const companyId = localStorage.getItem('company_id'); // Assuming stored
        const companyChannel = echo.private(`company.${companyId}`);

        // New resource share
        companyChannel.listen('.resource.shared', (event) => {
            setPendingCount(prev => prev + 1);
            showToast(`New shared resource: ${event.resource_name}`);
        });

        // Resource share responded
        companyChannel.listen('.resource.share-responded', (event) => {
            setPendingCount(prev => Math.max(0, prev - 1));
            showToast(`Share ${event.action}: ${event.resource_name}`);
        });

        return () => {
            inboxChannel.stopListening('.attachment-request.created');
            inboxChannel.stopListening('.attachment-request.responded');
            companyChannel.stopListening('.resource.shared');
            companyChannel.stopListening('.resource.share-responded');
            echo.leave(`inbox.${userId}`);
            echo.leave(`company.${companyId}`);
        };
    }, [userId]);

    return (
        <div className="notification-badge">
            <BellIcon />
            {pendingCount > 0 && (
                <span className="badge">{pendingCount}</span>
            )}
        </div>
    );
}
```

### Notification List Component

```javascript
function NotificationList() {
    const [notifications, setNotifications] = useState([]);

    useEffect(() => {
        // Fetch pending notifications
        axios.get('/api/notifications/pending')
            .then(res => {
                const { attachment_requests, resource_shares } = res.data.data;
                const combined = [
                    ...attachment_requests,
                    ...resource_shares
                ].sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
                
                setNotifications(combined);
            });
    }, []);

    return (
        <div className="notification-list">
            {notifications.map(notif => (
                <div key={notif.id} className="notification-item">
                    {notif.type === 'attachment_request' ? (
                        <div>
                            <strong>{notif.name}</strong>
                            <p>From: {notif.sender_company}</p>
                            <small>{new Date(notif.created_at).toLocaleString()}</small>
                        </div>
                    ) : (
                        <div>
                            <strong>{notif.resource_name}</strong>
                            <p>Shared by: {notif.owner_company}</p>
                            <small>{new Date(notif.created_at).toLocaleString()}</small>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}
```

---

## 🚀 Production Deployment

### 1. Supervisor for Reverb

**File:** `/etc/supervisor/conf.d/reverb.conf`

```ini
[program:reverb]
command=php /var/www/api/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/var/www/api
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/api/storage/logs/reverb.log
```

### 2. Supervisor for Queue Worker

**File:** `/etc/supervisor/conf.d/queue-worker.conf`

```ini
[program:queue-worker]
command=php /var/www/api/artisan queue:work --sleep=3 --tries=3
directory=/var/www/api
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/api/storage/logs/worker.log
```

### 3. Reload Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb queue-worker
```

---

## 📊 Testing Checklist

### Attachment Requests
- [ ] Create attachment request → Receiver users get real-time notification
- [ ] Approve request → Sender users get real-time notification
- [ ] Decline request → Sender users get real-time notification
- [ ] Pending count increases/decreases correctly

### Resource Shares
- [ ] Share resource → Shared-with company users get notification
- [ ] Accept share → Owner company users get notification
- [ ] Reject share → Owner company users get notification
- [ ] Pending count updates in real-time

### Notification API
- [ ] `/api/notifications/pending-counts` returns correct counts
- [ ] `/api/notifications/pending` returns detailed list
- [ ] Counts update in real-time as events occur

### Frontend
- [ ] WebSocket connection established
- [ ] Events received in browser console
- [ ] Badge count updates without refresh
- [ ] Toast notifications appear
- [ ] Notification list updates

---

## 🐛 Troubleshooting

### Events Not Broadcasting

```bash
# Check queue worker is running
php artisan queue:work

# Check Reverb server is running
php artisan reverb:start --debug

# Verify .env
BROADCAST_CONNECTION=reverb
```

### Frontend Not Receiving Events

1. Check browser console for WebSocket connection
2. Verify user_id in channel name matches auth user
3. Check Authorization header in Echo config
4. Verify CORS allows WebSocket connections

### Port 8080 Already in Use

```bash
# Windows
netstat -ano | findstr :8080
taskkill /PID <PID> /F

# Linux/Mac
lsof -ti:8080 | xargs kill -9

# Or use different port
php artisan reverb:start --port=8081
```

---

## 📚 Documentation Files

- `LARAVEL_REVERB_SETUP.md` - Complete setup guide with React integration
- `REVERB_QUICK_START.md` - Quick reference for commands and testing
- `ATTACHMENT_MEDIA_REFACTOR.md` - Attachment system refactoring details
- `BROADCASTING_IMPLEMENTATION_SUMMARY.md` - This file

---

## 🎉 Implementation Complete!

All components for real-time notifications have been implemented:

✅ Broadcasting configuration  
✅ Attachment request events  
✅ Resource share events  
✅ Service integration  
✅ Unified notification API  
✅ Frontend integration examples  
✅ Production deployment guides  

**Next Steps:**
1. Run `php artisan install:broadcasting`
2. Start Reverb and queue worker
3. Test with Postman/frontend
4. Deploy to production with Supervisor
