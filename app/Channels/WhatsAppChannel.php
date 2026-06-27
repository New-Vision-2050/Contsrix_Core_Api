<?php

namespace App\Channels;

class WhatsAppChannel
{
    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, $notification)
    {
        $message = $notification->toWhatsapp($notifiable);

        $message->send();
    }
}
