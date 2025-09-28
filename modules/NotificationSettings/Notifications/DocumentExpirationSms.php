<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Modules\NotificationSettings\Models\NotificationSettings;
use Carbon\Carbon;

class DocumentExpirationSms extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $documents,
        public NotificationSettings $notificationSetting,
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
    public function toSms(object $notifiable): string
    {
        $totalCount = $this->documents->count();
        $expiredCount = $this->getExpiredDocuments()->count();
        $upcomingCount = $this->getUpcomingDocuments()->count();
        
        $message = "🚨 Document Alert:\n";
        
        if ($expiredCount > 0) {
            $message .= "⚠️ {$expiredCount} documents EXPIRED\n";
        }
        
        if ($upcomingCount > 0) {
            $message .= "📅 {$upcomingCount} documents due TODAY\n";
        }
        
        $message .= "📋 Total: {$totalCount} documents require attention\n";
        
        // Add custom message if provided
        if ($this->notificationSetting->message) {
            $message .= "\n💬 " . substr($this->notificationSetting->message, 0, 100) . "\n";
        }
        
        // Add document details (limit to first 3 for SMS)
        $topDocuments = $this->documents->take(3);
        foreach ($topDocuments as $document) {
            $status = Carbon::parse($document->notification_date)->isPast() ? '❌' : '⏰';
            $companyName = $document->company?->name ?? 'Unknown Company';
            $docType = $document->documentType?->name ?? 'Document';
            
            $message .= "{$status} {$docType} - {$companyName}\n";
        }
        
        if ($this->documents->count() > 3) {
            $remaining = $this->documents->count() - 3;
            $message .= "... and {$remaining} more\n";
        }
        
        $message .= "\nCheck the system for full details.";
        
        // Ensure SMS doesn't exceed typical limits (160 chars for single SMS, 480 for multi-part)
        return substr($message, 0, 480);
    }

    /**
     * Get expired documents (notification_date has passed)
     */
    private function getExpiredDocuments(): Collection
    {
        return $this->documents->filter(function ($document) {
            return Carbon::parse($document->notification_date)->isPast();
        });
    }

    /**
     * Get upcoming documents (notification_date is today)
     */
    private function getUpcomingDocuments(): Collection
    {
        return $this->documents->filter(function ($document) {
            return Carbon::parse($document->notification_date)->isToday();
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
            'type' => 'document_expiration',
            'documents_count' => $this->documents->count(),
            'expired_count' => $this->getExpiredDocuments()->count(),
            'upcoming_count' => $this->getUpcomingDocuments()->count(),
            'notification_setting_id' => $this->notificationSetting->id,
        ];
    }
}
