<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Modules\NotificationSettings\Models\NotificationSettings;
use Carbon\Carbon;

class DocumentExpirationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $documents,
        public NotificationSettings $notificationSetting,
        public string $itemType = 'document',
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->getEmailSubject();

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'notification-settings::emails.document-expiration',
            with: [
                'documents' => $this->documents,
                'notificationSetting' => $this->notificationSetting,
                'totalDocuments' => $this->documents->count(),
                'expiredDocuments' => $this->getExpiredDocuments(),
                'upcomingDocuments' => $this->getUpcomingDocuments(),
                'customMessage' => $this->notificationSetting->message,
                'itemType' => $this->itemType,
            ],
        );
    }

    /**
     * Get email subject based on item status
     */
    private function getEmailSubject(): string
    {
        $totalCount = $this->documents->count();
        $expiredCount = $this->getExpiredDocuments()->count();
        $itemLabel = $this->itemType === 'file' ? 'ملف' : 'مستند';
        $itemsLabel = $this->itemType === 'file' ? 'ملفات' : 'مستندات';

        if ($expiredCount > 0) {
            return "🚨 تنبيه انتهاء صلاحية {$itemLabel} - {$expiredCount} منتهية، {$totalCount} إجمالي {$itemsLabel}";
        }

        return "📋 إشعار {$itemLabel} - {$totalCount} {$itemsLabel} تحتاج إلى اهتمام";
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
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
