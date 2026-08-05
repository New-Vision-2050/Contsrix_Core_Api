<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Presenters;

use Carbon\Carbon;
use Modules\Project\ProjectManagement\Models\ProjectNotification;
use Modules\Project\ProjectType\Models\ProjectOrderPermit;

/**
 * Dedicated map presenter for GET /projects/my-map-tasks.
 * Kept separate from ProjectNotificationPresenter::toMapArray() because this
 * endpoint merges notifications + order permits and always exposes contractor.
 */
class MyMapTaskPresenter
{
    public static function fromNotification(ProjectNotification $notification): array
    {
        $locationConfirmed = $notification->location_confirmed_at !== null;
        $received = $notification->confirmation_receive_date !== null;
        $status = self::resolvePseudoStatus($notification->status, $locationConfirmed, $received);

        return [
            'id' => $notification->id,
            'type' => 'notification',
            'notification_number' => $notification->notification_number,
            'task_name' => $notification->work_description,
            'latitude' => $notification->task_latitude ? (float) $notification->task_latitude : null,
            'longitude' => $notification->task_longitude ? (float) $notification->task_longitude : null,
            'radius' => $notification->location_radius ? (int) $notification->location_radius : null,
            'status' => $status,
            'status_label' => self::statusLabel($status),
            'contractor' => self::formatContractor($notification->contractor)
                ?? self::formatNotificationContractorFallback($notification),
            // 'assigned_users' => self::formatAssignedUsers($notification),
            // 'assigned_user' => self::formatFirstAssignedUser($notification),
            'receive_date' => self::formatDate($notification->confirmation_receive_date),
            'is_read' => (bool) $notification->getAttribute('is_read'),
        ];
    }

    public static function fromOrderPermit(ProjectOrderPermit $permit): array
    {
        $status = 'pending';
        $assignedUser = $permit->employee;

        $assignedUsers = $assignedUser
            ? [[
                'id' => $assignedUser->id,
                'name' => $assignedUser->name,
                'phone' => $assignedUser->phone ?? null,
            ]]
            : [];

        return [
            'id' => $permit->id,
            'type' => 'order_permit',
            'notification_number' => $permit->name,
            'task_name' => $permit->description_details ?? $permit->type ?? $permit->name,
            'latitude' => $permit->lat !== null ? (float) $permit->lat : null,
            'longitude' => $permit->long !== null ? (float) $permit->long : null,
            'radius' => null,
            'status' => $status,
            'status_label' => self::statusLabel($status),
            'contractor' => self::formatContractor($permit->contractor),
            // 'assigned_users' => $assignedUsers,
            // 'assigned_user' => $assignedUsers[0] ?? null,
            'receive_date' => self::formatDate($permit->assigned_date),
            'is_read' => false,
        ];
    }

    /**
     * @param  iterable<int, ProjectNotification>  $notifications
     * @param  iterable<int, ProjectOrderPermit>  $orderPermits
     * @return list<array<string, mixed>>
     */
    public static function collection(iterable $notifications, iterable $orderPermits): array
    {
        $items = [];

        foreach ($notifications as $notification) {
            $items[] = self::fromNotification($notification);
        }

        foreach ($orderPermits as $permit) {
            $items[] = self::fromOrderPermit($permit);
        }

        return $items;
    }

    /**
     * @return list<array{key: string, label_ar: string, label_en: string}>
     */
    public static function statusLookup(): array
    {
        $statuses = ['draft', 'pending', 'received', 'confirmed_location', 'completed'];
        $result = [];

        foreach ($statuses as $status) {
            $result[] = [
                'key' => $status,
                'label_ar' => self::statusLabel($status, 'ar'),
                'label_en' => self::statusLabel($status, 'en'),
            ];
        }

        return $result;
    }

    private static function formatContractor(mixed $contractor): ?array
    {
        if (! $contractor) {
            return null;
        }

        return [
            'id' => $contractor->id,
            'name' => $contractor->name,
        ];
    }

    private static function formatNotificationContractorFallback(ProjectNotification $notification): ?array
    {
        if (! $notification->contractor_id && ! $notification->contractor_name) {
            return null;
        }

        return [
            'id' => $notification->contractor_id,
            'name' => $notification->contractor_name,
        ];
    }

    private static function formatAssignedUsers(ProjectNotification $notification): array
    {
        return $notification->assigned_users->map(fn ($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ])->values()->all();
    }

    private static function formatFirstAssignedUser(ProjectNotification $notification): ?array
    {
        $user = $notification->assigned_user;

        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
        ];
    }

    private static function formatDate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        if ($date instanceof Carbon) {
            return $date->format('Y-m-d H:i:s');
        }

        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    private static function resolvePseudoStatus(string $status, bool $locationConfirmed, bool $received): string
    {
        if ($status === 'draft') {
            return 'draft';
        }

        if ($status === 'in_progress') {
            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        if ($status === 'cancelled') {
            if (! $received) {
                return 'pending';
            }

            return $locationConfirmed ? 'confirmed_location' : 'received';
        }

        return $status;
    }

    private static function statusLabel(string $status, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        $labels = [
            'draft' => ['ar' => 'مسودة', 'en' => 'Draft'],
            'pending' => ['ar' => 'بانتظار الرد', 'en' => 'Pending'],
            'received' => ['ar' => 'تم الاستلام', 'en' => 'Received'],
            'confirmed_location' => ['ar' => 'تم تأكيد الموقع', 'en' => 'Confirmed Location'],
            'completed' => ['ar' => 'مكتمل', 'en' => 'Completed'],
        ];

        return $labels[$status][$locale] ?? $status;
    }
}
