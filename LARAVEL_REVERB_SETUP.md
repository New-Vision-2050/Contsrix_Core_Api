# Laravel Reverb Broadcasting Setup Guide

## Overview
This guide covers the complete setup of Laravel Reverb for real-time WebSocket broadcasting in the Attachment Request system.

## Installation Steps

### 1. Install Laravel Broadcasting (Reverb)

Run the official Laravel broadcasting installation command:

```bash
php artisan install:broadcasting
```

This command will:
- Install the `laravel/reverb` package via Composer
- Publish the broadcasting configuration
- Create the `routes/channels.php` file
- Update your `.env` file with Reverb credentials
- Install and configure the Laravel Echo JavaScript library (optional)

**If the package is not found**, update Composer first:

```bash
composer require laravel/reverb
```

Then run:

```bash
php artisan install:broadcasting
```

### 2. Configure Environment Variables

Add the following to your `.env` file:

```env
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

**Note**: The installation command will generate these credentials automatically.

### 3. Start the Reverb Server

Start the WebSocket server:

```bash
php artisan reverb:start
```

**For development with auto-reload:**

```bash
php artisan reverb:start --debug
```

**For production:**

```bash
php artisan reverb:start --host=0.0.0.0 --port=8080
```

### 4. Configure Queue Worker (Important!)

Broadcasting events are queued by default. Start a queue worker:

```bash
php artisan queue:work
```

**For development:**

```bash
php artisan queue:listen
```

### 5. Publish Configuration (Optional)

If you need to customize Reverb settings:

```bash
php artisan vendor:publish --tag=reverb-config
```

This creates `config/reverb.php` for advanced configuration.

## Architecture

### Events Created

#### 1. AttachmentRequestCreated
**Location**: `modules/Project/ProjectManagement/Events/AttachmentRequestCreated.php`

**Triggered when**: A new attachment request is created

**Broadcasts to**: `inbox.{receiver_user_id}`

**Data sent**:
```json
{
    "id": "uuid",
    "serial_number": "ATR-20260412-0001",
    "name": "Request Name",
    "sender_company_id": "uuid",
    "sender_company_name": "Company A",
    "receiver_company_id": "uuid",
    "receiver_company_name": "Company B",
    "project_id": "uuid",
    "project_name": "Project Name",
    "total_items": 5,
    "status": "pending",
    "created_by": {
        "id": "uuid",
        "name": "John Doe"
    },
    "created_at": "2026-04-12T11:00:00.000000Z",
    "notification_type": "attachment_request"
}
```

#### 2. AttachmentRequestResponded
**Location**: `modules/Project/ProjectManagement/Events/AttachmentRequestResponded.php`

**Triggered when**: An attachment request is approved/declined

**Broadcasts to**: `inbox.{sender_user_id}`

**Data sent**:
```json
{
    "id": "uuid",
    "serial_number": "ATR-20260412-0001",
    "name": "Request Name",
    "sender_company_id": "uuid",
    "receiver_company_id": "uuid",
    "receiver_company_name": "Company B",
    "project_id": "uuid",
    "project_name": "Project Name",
    "status": "approved",
    "action": "approved",
    "responded_by": {
        "id": "uuid",
        "name": "Jane Smith"
    },
    "responded_at": "2026-04-12T12:00:00.000000Z",
    "notification_type": "attachment_request_response"
}
```

### Service Integration

**File**: `modules/Project/ProjectManagement/Services/AttachmentRequestService.php`

**Broadcasting Methods**:
- `broadcastToReceiverCompany()` - Sends notifications to all users in receiver company
- `broadcastToSenderCompany()` - Sends notifications to all users in sender company

**Trigger Points**:
1. **Create Request** → Broadcasts to receiver company users
2. **Approve Request** → Broadcasts to sender company users
3. **Decline Request** → Broadcasts to sender company users

## React Frontend Integration

### 1. Install Laravel Echo and Pusher JS

```bash
npm install --save laravel-echo pusher-js
```

### 2. Configure Echo Client

Create `src/utils/echo.js`:

```javascript
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

const echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/api/broadcasting/auth', // For private channels
    auth: {
        headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`,
        },
    },
});

export default echo;
```

### 3. Listen to Events in React Component

```javascript
import { useEffect, useState } from 'react';
import echo from './utils/echo';

function NotificationsComponent() {
    const [notifications, setNotifications] = useState([]);
    const [pendingCount, setPendingCount] = useState(0);
    const userId = localStorage.getItem('user_id'); // Get from auth

    useEffect(() => {
        // Subscribe to user's inbox channel
        const channel = echo.channel(`inbox.${userId}`);

        // Listen for new attachment requests
        channel.listen('.attachment-request.created', (event) => {
            console.log('New attachment request:', event);
            
            setNotifications(prev => [...prev, {
                id: event.id,
                type: 'new_request',
                message: `New attachment request from ${event.sender_company_name}`,
                data: event,
                timestamp: event.created_at,
                read: false
            }]);

            setPendingCount(prev => prev + 1);

            // Show toast notification
            showToast(`New request: ${event.name}`);
        });

        // Listen for responses to requests
        channel.listen('.attachment-request.responded', (event) => {
            console.log('Request responded:', event);
            
            setNotifications(prev => [...prev, {
                id: event.id,
                type: 'request_response',
                message: `Request ${event.action}: ${event.name}`,
                data: event,
                timestamp: event.responded_at,
                read: false
            }]);

            // Show toast notification
            showToast(`Request ${event.action} by ${event.receiver_company_name}`);
        });

        // Cleanup on unmount
        return () => {
            channel.stopListening('.attachment-request.created');
            channel.stopListening('.attachment-request.responded');
            echo.leaveChannel(`inbox.${userId}`);
        };
    }, [userId]);

    return (
        <div className="notifications">
            <div className="notification-badge">
                {pendingCount > 0 && <span>{pendingCount}</span>}
            </div>
            <ul>
                {notifications.map(notif => (
                    <li key={notif.id}>
                        {notif.message}
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default NotificationsComponent;
```

### 4. Real-time Counter Example

```javascript
function PendingRequestsCounter() {
    const [count, setCount] = useState(0);
    const userId = localStorage.getItem('user_id');

    useEffect(() => {
        const channel = echo.channel(`inbox.${userId}`);

        channel.listen('.attachment-request.created', (event) => {
            setCount(prev => prev + 1);
        });

        channel.listen('.attachment-request.responded', (event) => {
            if (event.action === 'approved' || event.action === 'declined') {
                setCount(prev => Math.max(0, prev - 1));
            }
        });

        return () => {
            echo.leaveChannel(`inbox.${userId}`);
        };
    }, [userId]);

    return (
        <div className="pending-badge">
            {count > 0 && (
                <span className="badge">{count}</span>
            )}
        </div>
    );
}
```

## Testing

### 1. Test WebSocket Connection

```bash
# Terminal 1: Start Reverb server
php artisan reverb:start --debug

# Terminal 2: Start queue worker
php artisan queue:work --verbose

# Terminal 3: Test the API
curl -X POST http://localhost/api/projects/attachment-requests \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "attachments[]=@file.pdf"
```

### 2. Debug Events

Add logging to your event:

```php
use Illuminate\Support\Facades\Log;

public function broadcastWith(): array
{
    $data = [
        'id' => $this->attachmentRequest->id,
        // ...
    ];
    
    Log::info('Broadcasting attachment request', $data);
    
    return $data;
}
```

### 3. Monitor Reverb Connections

```bash
php artisan reverb:start --debug
```

You'll see:
- Connection events
- Broadcast events
- Channel subscriptions

## Production Deployment

### 1. Use Supervisor for Reverb

Create `/etc/supervisor/conf.d/reverb.conf`:

```ini
[program:reverb]
command=php /path/to/your/app/artisan reverb:start --host=0.0.0.0 --port=8080
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/reverb.log
```

Reload supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start reverb
```

### 2. Use Supervisor for Queue Worker

Create `/etc/supervisor/conf.d/queue-worker.conf`:

```ini
[program:queue-worker]
command=php /path/to/your/app/artisan queue:work --sleep=3 --tries=3
directory=/path/to/your/app
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/app/storage/logs/worker.log
```

### 3. Nginx Configuration (for WebSocket proxy)

Add to your nginx config:

```nginx
# WebSocket proxy for Reverb
location /app {
    proxy_http_version 1.1;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_pass http://127.0.0.1:8080;
}
```

### 4. Use SSL/TLS (Production)

Update `.env`:

```env
REVERB_SCHEME=https
REVERB_HOST=your-domain.com
```

Configure nginx with SSL and proxy WebSocket connections.

## Troubleshooting

### Issue: Events not broadcasting

**Solution**:
1. Check queue worker is running: `php artisan queue:work`
2. Check Reverb server is running: `php artisan reverb:start`
3. Verify `.env` has `BROADCAST_CONNECTION=reverb`

### Issue: Frontend not receiving events

**Solution**:
1. Check browser console for WebSocket connection
2. Verify API endpoint: `/api/broadcasting/auth`
3. Check CORS configuration allows WebSocket connections
4. Verify user_id in channel name matches authenticated user

### Issue: "Class 'Reverb' not found"

**Solution**:
```bash
composer require laravel/reverb
php artisan install:broadcasting
```

### Issue: Port 8080 already in use

**Solution**:
```bash
# Use different port
php artisan reverb:start --port=8081

# Update .env
REVERB_PORT=8081
```

## Performance Optimization

### 1. Use Redis for Broadcasting

Update `config/broadcasting.php`:

```php
'connections' => [
    'reverb' => [
        'driver' => 'reverb',
        // ... existing config
        'queue' => 'broadcasts', // Use dedicated queue
    ],
],
```

### 2. Optimize User Queries

Cache company users to reduce database queries:

```php
private function broadcastToReceiverCompany(AttachmentRequest $request): void
{
    $cacheKey = "company_users_{$request->receiver_company_id}";
    
    $users = Cache::remember($cacheKey, 3600, function () use ($request) {
        return User::where('company_id', $request->receiver_company_id)
            ->select('id')
            ->get();
    });

    foreach ($users as $user) {
        event(new AttachmentRequestCreated($request, (string) $user->id));
    }
}
```

### 3. Batch Broadcasting

For large user bases, consider batching:

```php
use Illuminate\Support\Facades\Bus;

$jobs = $users->map(function ($user) use ($request) {
    return new BroadcastNotification($request, $user->id);
});

Bus::batch($jobs)->dispatch();
```

## Security Considerations

### 1. Channel Authorization (if using private channels)

Create `routes/channels.php`:

```php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('inbox.{userId}', function ($user, $userId) {
    return (string) $user->id === (string) $userId;
});
```

### 2. Rate Limiting

Apply rate limiting to prevent abuse:

```php
use Illuminate\Support\Facades\RateLimiter;

public function createRequest(array $data): AttachmentRequest
{
    $key = 'create-request:' . Auth::id();
    
    if (RateLimiter::tooManyAttempts($key, 10)) {
        throw new \Exception('Too many requests. Please try again later.');
    }
    
    RateLimiter::hit($key, 60); // 10 requests per minute
    
    // ... rest of the code
}
```

## Files Modified/Created

### Created Files:
- ✅ `config/broadcasting.php` - Broadcasting configuration
- ✅ `modules/Project/ProjectManagement/Events/AttachmentRequestCreated.php`
- ✅ `modules/Project/ProjectManagement/Events/AttachmentRequestResponded.php`

### Modified Files:
- ✅ `modules/Project/ProjectManagement/Services/AttachmentRequestService.php` - Added event broadcasting
- ⏳ `.env` - Add Reverb credentials (after running install command)
- ⏳ `routes/channels.php` - Will be created by install command

## Next Steps

1. ✅ Run `php artisan install:broadcasting`
2. ✅ Start Reverb server: `php artisan reverb:start --debug`
3. ✅ Start queue worker: `php artisan queue:work`
4. ✅ Test by creating an attachment request
5. ✅ Implement React frontend with Laravel Echo
6. ✅ Set up Supervisor for production
7. ✅ Configure SSL/TLS for production

## References

- [Laravel Broadcasting Documentation](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Reverb Documentation](https://laravel.com/docs/11.x/reverb)
- [Laravel Echo Documentation](https://github.com/laravel/echo)
- [Pusher JS Documentation](https://github.com/pusher/pusher-js)
