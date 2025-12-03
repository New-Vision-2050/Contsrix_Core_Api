<?php

declare(strict_types=1);

namespace Modules\WebsiteCMS\WebsiteContactMessage\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\WebsiteCMS\WebsiteContactMessage\Models\WebsiteContactMessage;

class ContactMessageReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        private WebsiteContactMessage $contactMessage,
        private string $replyMessage
    ) {
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
            ->subject(__('emails.subject'))
            ->greeting(__('emails.greeting', ['name' => $this->contactMessage->name]))
            ->line(__('emails.thank_you'))
            ->line('')
            ->line('**' . __('emails.original_message') . '**')
            ->line($this->contactMessage->message)
            ->line('')
            ->line('**' . __('emails.our_reply') . '**')
            ->line($this->replyMessage)
            ->line('')
            ->line(__('emails.further_questions'))
            ->salutation(__('emails.salutation') . ' ' . config('app.name'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'contact_message_id' => $this->contactMessage->id,
            'reply_message' => $this->replyMessage,
        ];
    }
}
