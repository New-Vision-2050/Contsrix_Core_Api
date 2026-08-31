<?php

declare(strict_types=1);

namespace Modules\Attendance\Notifications;

use App\Notifications\Drivers\Voice\TwilioVoice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Modules\Attendance\Support\OutZoneClockOutWarning;

/**
 * Same pattern as SiteStatusUpdateRequiredVoiceNotification: send Twilio
 * immediately (not queued). Queued voice never dials here — queue workers
 * lose tenant Twilio config, and QUEUE_CONNECTION=database needs queue:work.
 */
class OutZoneClockOutWarningNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return ['voice'];
    }

    public function toVoice(object $notifiable): TwilioVoice
    {
        $driver = new TwilioVoice;
        $fullPhone = $this->buildInternationalPhoneNumber($notifiable);
        $message = htmlspecialchars(OutZoneClockOutWarning::VOICE_MESSAGE, ENT_XML1, 'UTF-8');

        Log::info('OutZoneClockOutWarningNotification: preparing voice call', [
            'notifiable_id' => $notifiable->id ?? null,
            'notifiable_phone' => $notifiable->phone ?? null,
            'full_phone' => $fullPhone,
        ]);

        $twiml = <<<TWIML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Zeina" language="ar-EG">{$message}</Say>
    <Pause length="1"/>
    <Say voice="Polly.Zeina" language="ar-EG">{$message}</Say>
</Response>
TWIML;

        return $driver
            ->to($fullPhone)
            ->twiml($twiml);
    }

    public function buildInternationalPhoneNumber(object $notifiable): string
    {
        $phone = preg_replace('/[\s\-]/', '', trim((string) ($notifiable->phone ?? ''))) ?? '';
        $phoneCode = trim((string) ($notifiable->phone_code ?? ''));

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+')) {
            return $phone;
        }

        $code = ltrim($phoneCode, '+');
        if ($code !== '') {
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }

            if (str_starts_with($phone, $code)) {
                return '+' . $phone;
            }

            return '+' . $code . $phone;
        }

        return $phone;
    }
}
