<?php

declare(strict_types=1);

namespace Modules\Attendance\Notifications;

use App\Notifications\Drivers\Voice\TwilioVoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Modules\Attendance\Support\OutZoneClockOutWarning;

class OutZoneClockOutWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['voice'];
    }

    public function toVoice(object $notifiable): TwilioVoice
    {
        $driver = new TwilioVoice;
        $message = htmlspecialchars(OutZoneClockOutWarning::VOICE_MESSAGE, ENT_XML1, 'UTF-8');

        $twiml = <<<TWIML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Zeina" language="ar-EG">{$message}</Say>
    <Pause length="1"/>
    <Say voice="Polly.Zeina" language="ar-EG">{$message}</Say>
</Response>
TWIML;

        return $driver
            ->to($this->buildInternationalPhoneNumber($notifiable))
            ->twiml($twiml);
    }

    private function buildInternationalPhoneNumber(object $notifiable): string
    {
        $phone = trim((string) ($notifiable->phone ?? ''));
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

            return '+' . $code . $phone;
        }

        return $phone;
    }
}
