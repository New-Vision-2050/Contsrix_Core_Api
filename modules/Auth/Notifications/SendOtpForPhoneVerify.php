<?php

namespace Modules\Auth\Notifications;

use App\Notifications\Drivers\SMS\MoraSms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SendOtpForPhoneVerify extends Notification
{
    use Queueable;

    protected $data;
    protected $smsDriver;

    public function __construct($data)
    {
        $this->data = $data;
        $this->smsDriver = new MoraSms(); // Always MoraSms
    }

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return $this->smsDriver
            ->to($notifiable->phone)
            ->line(__('emails.login-with-otp') . ' ' . $this->data['otp']);
    }

    public function toArray($notifiable)
    {
        return [];
    }
}
