<?php

declare(strict_types=1);

namespace Modules\Shared\Notification\Controllers;

use Illuminate\Http\JsonResponse;
use Modules\Shared\Notification\Services\NotificationCountService;
use BasePackage\Shared\Controllers\BaseController;

class NotificationController extends BaseController
{
    public function __construct(
        private NotificationCountService $notificationCountService
    ) {
    }

    /**
     * Get pending notification counts
     *
     * @return JsonResponse
     */
    public function getPendingCounts(): JsonResponse
    {
        try {
            $counts = $this->notificationCountService->getPendingCounts();

            return $this->successResponse(
                data: $counts,
                message: 'Pending notification counts retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to get notification counts: ' . $e->getMessage(),
                code: 500
            );
        }
    }

    /**
     * Get detailed pending notifications
     *
     * @return JsonResponse
     */
    public function getPendingNotifications(): JsonResponse
    {
        try {
            $notifications = $this->notificationCountService->getPendingNotifications();

            return $this->successResponse(
                data: $notifications,
                message: 'Pending notifications retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to get pending notifications: ' . $e->getMessage(),
                code: 500
            );
        }
    }
}
