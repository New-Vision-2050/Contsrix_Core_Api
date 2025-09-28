<?php

declare(strict_types=1);

namespace Modules\NotificationSettings\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Modules\NotificationSettings\Models\NotificationSettings;
use Carbon\Carbon;

class DocumentExpirationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Collection $documents,
        public NotificationSettings $notificationSetting,
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
            ],
        );
    }

    /**
     * Get email subject based on document status
     */
    private function getEmailSubject(): string
    {
        $totalCount = $this->documents->count();
        $expiredCount = $this->getExpiredDocuments()->count();
        
        if ($expiredCount > 0) {
            return "🚨 Document Expiration Alert - {$expiredCount} Expired, {$totalCount} Total Documents";
        }
        
        return "📋 Document Notification - {$totalCount} Documents Require Attention";
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
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
