<?php

namespace Modules\Project\ProjectManagement\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Project\ProjectManagement\Models\ProjectManagement;

class EmployeeAssignedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectManagement $project,
        public string $employeeName,
        public string $assignedByName,
        public ?string $roleName,
        public string $actionUrl
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'تم تعيينك في مشروع - ' . $this->project->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'project-management::emails.employee-assigned',
            with: [
                'employeeName' => $this->employeeName,
                'assignedByName' => $this->assignedByName,
                'projectName' => $this->project->name,
                'projectDescription' => $this->project->description,
                'roleName' => $this->roleName ?? 'موظف',
                'actionUrl' => $this->actionUrl,
                'year' => date('Y'),
                'supportEmail' => config('mail.support_email', 'info@nv2030.com'),
            ],
        );
    }
}
