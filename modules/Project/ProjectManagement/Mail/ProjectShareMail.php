<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Project\ProjectManagement\Models\ProjectManagement;
use Modules\Shared\ResourceShare\Models\ResourceShare;

class ProjectShareMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ResourceShare $share,
        public ProjectManagement $project,
        public string $recipientName,
        public string $senderName,
        public string $actionUrl
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'طلب موافقة على مشاركة مشروع - Constrix',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'project-management::emails.project-share',
            with: [
                'userName' => $this->recipientName,
                'referenceNo' => $this->project->reference_number ?? $this->project->serial_number,
                'projectName' => $this->project->name,
                'entityType' => 'مشروع',
                'entityName' => $this->project->name,
                'status' => $this->getStatusLabel(),
                'priority' => $this->getPriorityLabel(),
                'senderName' => $this->senderName,
                'createdAt' => $this->share->created_at->format('Y-m-d H:i'),
                'actionUrl' => $this->actionUrl,
                'year' => date('Y'),
                'supportEmail' => "info@nv2030.com",
            ],
        );
    }

    /**
     * Get status label in Arabic
     */
    private function getStatusLabel(): string
    {
        return match ($this->share->status) {
            'pending' => 'قيد المراجعة',
            'accepted' => 'مقبول',
            'rejected' => 'مرفوض',
            default => 'قيد المراجعة',
        };
    }

    /**
     * Get priority label in Arabic
     */
    private function getPriorityLabel(): string
    {
        // You can enhance this based on your project priority field
        return 'عالية جداً';
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
