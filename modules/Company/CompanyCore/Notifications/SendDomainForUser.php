<?php

namespace Modules\Company\CompanyCore\Notifications;

use App\Channels\SmsChannel;
use App\Notifications\Drivers\SMS\MoraSms;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Country\Models\Country;

class SendDomainForUser extends Notification
{
    protected $data;
    protected $types;
    protected $smsDriver;


    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data , array $types = ["mail"])

    {
        $this->smsDriver= new MoraSms();//as a default driver
        $this->data = $data;
        $this->types = $types;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */

    private function setDriverSMS($notifiable): void
    {
        $driverName = Country::query()->where("phonecode", str_replace("+","",$notifiable->phone_code))->first();
        if ($driverName && $driverName->smsDriver) {
            if ($driverName?->smsDriver?->name == "mora") {
                $this->smsDriver = new MoraSms();
            } else {
                //TODO if there is many sms provider choose default one
                $this->smsDriver = $driverName->smsDriver->name;
            }
        } else {
            //TODO if there is many sms provider choose default one
            $this->smsDriver = new MoraSms();
        }
    }


    public function via($notifiable)
    {
        $this->setDriverSMS($notifiable);

        return $this->types;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->subject("You are assigned in company")
            ->markdown('emails.sendDomainForUser', ['data' => $this->data]);
    }

    public function toSms($notifiable)
    {
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
