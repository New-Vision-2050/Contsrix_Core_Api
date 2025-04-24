<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpForEmailChange extends Notification
{
    use Queueable;

    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct( $data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        if (empty($this->data['otp']) || empty($this->data['email'])) {
            \Log::error('OTP or Email is missing in the notification data');
        }
        return (new MailMessage)
            ->subject(__('emails.change-your-email'))
            ->line(__('emails.you-tried-to-change-email-with',["email"=>$this->data['email']]) )
            ->line(__('emails.your-verification-code-is')." ". $this->data['otp'])
            ->line(__('emails.will-expire', ['time' => 3]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
