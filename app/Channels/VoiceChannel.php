<?php

namespace App\Channels;

use Illuminate\Support\Facades\Log;

class VoiceChannel
{
    public function send($notifiable, $notification)
    {
        try {
            $message = $notification->toVoice($notifiable);
            $result = $message->send();

            if ($result === false || $result === null) {
                Log::warning('VoiceChannel: send() returned false/null, call may not have been initiated', [
                    'notifiable_id' => $notifiable->id ?? null,
                    'notifiable_class' => get_class($notifiable),
                    'notification' => get_class($notification),
                ]);
            } else {
                Log::info('VoiceChannel: call initiated successfully', [
                    'notifiable_id' => $notifiable->id ?? null,
                    'result' => $result,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('VoiceChannel: exception during send', [
                'notifiable_id' => $notifiable->id ?? null,
                'notifiable_class' => get_class($notifiable),
                'notification' => get_class($notification),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
