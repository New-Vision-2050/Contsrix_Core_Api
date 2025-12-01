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
            ->subject('Reply to Your Contact Message | رد على رسالتك')
            ->greeting('Hello ' . $this->contactMessage->name . ' | مرحباً ' . $this->contactMessage->name)
            ->line('Thank you for contacting us. We have received your message and here is our response:')
            ->line('شكراً لتواصلك معنا. لقد استلمنا رسالتك وإليك ردنا:')
            ->line('')
            ->line('**Your Original Message: | رسالتك الأصلية:**')
            ->line($this->contactMessage->message)
            ->line('')
            ->line('**Our Reply: | ردنا:**')
            ->line($this->replyMessage)
            ->line('')
            ->line('If you have any further questions, please feel free to contact us again.')
            ->line('إذا كان لديك أي أسئلة أخرى، لا تتردد في التواصل معنا مرة أخرى.')
            ->salutation('Best regards | مع أطيب التحيات, ' . config('app.name'));
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
