<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SafetyViolationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     contractor_name: string,
     *     work_order: string,
     *     notification_type: string,
     *     issue_date: string,
     *     visit_time: string,
     *     location: string,
     *     project_manager: string,
     *     safety_officer: string,
     *     site_supervisor: string,
     *     total_fine: string,
     *     first_violation_code: string,
     *     violations: list<array{label: string, value: string}>,
     *     report_url: string|null
     * }  $data
     */
    public function __construct(
        public array $data
    ) {}

    public function envelope(): Envelope
    {
        $workOrder = $this->data['work_order'] !== ''
            ? ' - '.$this->data['work_order']
            : '';

        return new Envelope(
            subject: 'إشعار مخالفة سلامة'.$workOrder,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'project-type::emails.safety-violation',
            with: $this->data,
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
