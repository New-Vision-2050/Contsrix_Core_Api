<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Notification\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Notification API Routes
|--------------------------------------------------------------------------
|
| These routes handle real-time notification counts and pending items
| for attachment requests and resource shares.
|
*/

Route::prefix('notifications')->middleware(['auth:sanctum'])->group(function () {
    // Get pending notification counts
    Route::get('/pending-counts', [NotificationController::class, 'getPendingCounts'])
        ->name('notifications.pending-counts');

    // Get detailed pending notifications
    Route::get('/pending', [NotificationController::class, 'getPendingNotifications'])
        ->name('notifications.pending');
});
