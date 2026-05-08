<?php

namespace Modules\Project\ProjectManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Project\ProjectManagement\Models\AttachmentRequest;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class AttachmentRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AttachmentRequest $attachmentRequest,
        public ProjectManagement $project,
        public string $recipientName,
        public string $senderName,
        public string $actionUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'طلب مرفقات جديد - ' . $this->attachmentRequest->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'project-management::emails.attachment-request',
            with: [
                'recipientName' => $this->recipientName,
                'senderName' => $this->senderName,
                'requestName' => $this->attachmentRequest->name,
                'projectName' => $this->project->name,
                'serialNumber' => $this->attachmentRequest->serial_number,
                'date' => $this->attachmentRequest->date,
                'totalItems' => $this->attachmentRequest->items->count(),
                'notes' => $this->attachmentRequest->notes,
                'createdAt' => $this->attachmentRequest->created_at->format('Y-m-d H:i'),
                'actionUrl' => $this->actionUrl,
                'year' => date('Y'),
                'supportEmail' => config('mail.support_email', 'info@nv2030.com'),
            ],
        );
    }
}
