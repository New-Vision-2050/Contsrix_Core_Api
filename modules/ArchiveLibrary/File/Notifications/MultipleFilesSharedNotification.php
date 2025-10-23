<?php

declare(strict_types=1);

namespace Modules\ArchiveLibrary\File\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class MultipleFilesSharedNotification extends Notification
{
    use Queueable;

    private array $shareUrls;
    private Collection $files;
    private string $sharedByName;

    public function __construct(array $shareUrls, Collection $files, string $sharedByName)
    {
        $this->shareUrls = $shareUrls;
        $this->files = $files;
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
        $filesCount = $this->files->count();
        $subject = $filesCount === 1 
            ? 'A File Has Been Shared With You' 
            : $filesCount . ' Files Have Been Shared With You';

        $mailMessage = (new MailMessage)
            ->subject($subject)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line($this->sharedByName . ' has shared ' . $filesCount . ' ' . ($filesCount === 1 ? 'file' : 'files') . ' with you.');

        // Add details for each file
        foreach ($this->files as $index => $file) {
            $mailMessage->line('---');
            $mailMessage->line('**File ' . ($index + 1) . ':**');
            $mailMessage->line('• Name: ' . $file->name);
            $mailMessage->line('• Reference Number: ' . $file->reference_number);
            $mailMessage->line('• Start Date: ' . ($file->start_date ? $file->start_date->format('Y-m-d') : 'N/A'));
            $mailMessage->line('• End Date: ' . ($file->end_date ? $file->end_date->format('Y-m-d') : 'N/A'));
            
            // Add individual file link
            if (isset($this->shareUrls[$index])) {
                $mailMessage->action('View ' . $file->name, $this->shareUrls[$index]);
            }
        }

        $mailMessage->line('---');
        $mailMessage->line('You can access ' . ($filesCount === 1 ? 'this file' : 'these files') . ' using the links above.');
        $mailMessage->line('Thank you for using our application!');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'files_count' => $this->files->count(),
            'files' => $this->files->map(function ($file, $index) {
                return [
                    'file_id' => $file->id,
                    'file_name' => $file->name,
                    'share_url' => $this->shareUrls[$index] ?? null,
                ];
            })->toArray(),
            'shared_by' => $this->sharedByName,
        ];
    }
}
