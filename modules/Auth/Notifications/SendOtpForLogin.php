<?php

namespace Modules\Auth\Notifications;

use App\Channels\SmsChannel;
use App\Notifications\Drivers\SMS\MoraSms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class SendOtpForLogin extends Notification
{
    use Queueable;
    protected $data;
    protected $types;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data , array $types = ["mail"])

    {
        $this->data = $data;
        $this->types = $types;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {


        return ["mail","sms"];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject(__('emails.login-with-otp'))
            ->markdown('emails.loginWithOtp', ['data' => $this->data]);
    }

    public function toSms($notifiable)
    {
        // We are assuming we are notifying a user or a model that has a telephone attribute/field.
        // And the telephone number is correctly formatted.
        // TODO: SmsMessage, doesn't exist yet :-) We should create it.
        return (new MoraSms())
            ->to($notifiable->phone)
            ->line(__("emails.login-with-otp").$this->data['otp']);
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
