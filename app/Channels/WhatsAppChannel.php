<?php

namespace App\Channels;

use Illuminate\Support\Facades\Log;

class WhatsAppChannel
{
    public function send($notifiable, $notification)
    {
        try {
            $message = $notification->toWhatsapp($notifiable);
            $result = $message->send();

            if ($result === false || $result === null) {
                Log::warning('WhatsAppChannel: send() returned false/null, message may not have been delivered', [
                    'notifiable_id' => $notifiable->id ?? null,
                    'notifiable_class' => get_class($notifiable),
                    'notification' => get_class($notification),
                ]);
            } else {
                Log::info('WhatsAppChannel: message dispatched successfully', [
                    'notifiable_id' => $notifiable->id ?? null,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('WhatsAppChannel: exception during send', [
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_class' => get_class($notifiable),
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
