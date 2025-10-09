<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\ArchiveLibrary\File\Models\File;

class FileSharedNotification extends Notification
{
    use Queueable;

    private string $shareUrl;
    private File $file;
    private string $sharedByName;

    public function __construct(string $shareUrl, File $file, string $sharedByName)
    {
        $this->shareUrl = $shareUrl;
        $this->file = $file;
        $this->sharedByName = $sharedByName;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A File Has Been Shared With You')
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->sharedByName . ' has shared a file with you.')
            ->line('**File Details:**')
            ->line('Name: ' . $this->file->name)
            ->line('Reference Number: ' . $this->file->reference_number)
            ->line('Start Date: ' . ($this->file->start_date ? $this->file->start_date->format('Y-m-d') : 'N/A'))
            ->line('End Date: ' . ($this->file->end_date ? $this->file->end_date->format('Y-m-d') : 'N/A'))
            ->action('View Shared File', $this->shareUrl)
            ->line('You can access this file using the link above.')
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'file_id' => $this->file->id,
            'file_name' => $this->file->name,
            'share_url' => $this->shareUrl,
            'shared_by' => $this->sharedByName,
        ];
    }
}
