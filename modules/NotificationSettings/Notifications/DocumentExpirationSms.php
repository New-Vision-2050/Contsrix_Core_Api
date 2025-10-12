<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Modules\NotificationSettings\Models\NotificationSettings;
use Carbon\Carbon;
use App\Notifications\Drivers\SMS\MoraSms;

class DocumentExpirationSms extends Notification
{
    use Queueable;

    public function __construct(
        public Collection $documents,
        public NotificationSettings $notificationSetting,
        public string $itemType = 'document',
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['sms'];
    }

    /**
     * Get the SMS representation of the notification.
     */
    public function toSms(object $notifiable): MoraSms
    {
        $totalCount = $this->documents->count();
        $expiredCount = $this->getExpiredDocuments()->count();
        $upcomingCount = $this->getUpcomingDocuments()->count();
        $itemLabel = $this->itemType === 'file' ? 'ملف' : 'مستند';
        $itemsLabel = $this->itemType === 'file' ? 'ملفات' : 'مستندات';

        $messageText = "🚨 تنبيه {$itemLabel}:\n";

        if ($expiredCount > 0) {
            $messageText .= "⚠️ {$expiredCount} {$itemsLabel} منتهية الصلاحية\n";
        }

        if ($upcomingCount > 0) {
            $messageText .= "📅 {$upcomingCount} {$itemsLabel} تنتهي اليوم\n";
        }

        $messageText .= "📋 الإجمالي: {$totalCount} {$itemsLabel} تحتاج إلى اهتمام\n";

        // Add custom message if provided
        if ($this->notificationSetting->message) {
            $messageText .= "\n💬 " . substr($this->notificationSetting->message, 0, 100) . "\n";
        }

        // Add ALL item details
        $dateField = $this->itemType === 'file' ? 'end_date' : 'notification_date';

        foreach ($this->documents as $item) {
            $status = Carbon::parse($item->$dateField)->isPast() ? '❌' : '⏰';

            if ($this->itemType === 'file') {
                $itemName = $item->name ?? 'ملف بدون اسم';
                $referenceNum = $item->reference_number ? " ({$item->reference_number})" : '';
                $messageText .= "{$status} {$itemName}{$referenceNum}\n";
            } else {
                $companyName = $item->company?->name ?? 'شركة غير معروفة';
                $docType = $item->documentType?->name ?? 'مستند';
                $messageText .= "{$status} {$docType} - {$companyName}\n";
            }
        }

        $messageText .= "\nتحقق من النظام للحصول على التفاصيل الكاملة.";

        // Return MoraSms object
        return (new MoraSms())
            ->to($notifiable->routeNotificationForSms())
            ->message($messageText);
    }

    /**
     * Get expired items (notification_date/end_date has passed)
     */
    private function getExpiredDocuments(): Collection
    {
        $dateField = $this->itemType === 'file' ? 'end_date' : 'notification_date';

        return $this->documents->filter(function ($item) use ($dateField) {
            return Carbon::parse($item->$dateField)->isPast();
        });
    }

    /**
     * Get upcoming items (notification_date/end_date is today)
     */
    private function getUpcomingDocuments(): Collection
    {
        $dateField = $this->itemType === 'file' ? 'end_date' : 'notification_date';

        return $this->documents->filter(function ($item) use ($dateField) {
            return Carbon::parse($item->$dateField)->isToday();
        });
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->itemType === 'file' ? 'file_expiration' : 'document_expiration',
            'item_type' => $this->itemType,
            'documents_count' => $this->documents->count(),
            'expired_count' => $this->getExpiredDocuments()->count(),
            'upcoming_count' => $this->getUpcomingDocuments()->count(),
            'notification_setting_id' => $this->notificationSetting->id,
        ];
    }
}
