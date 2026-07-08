<?php

declare(strict_types=1);

namespace Modules\Project\ProjectManagement\Notifications;

use App\Notifications\Drivers\Voice\TwilioVoice;
use Illuminate\Notifications\Notification;
use Modules\Project\ProjectManagement\Models\ProjectNotification;

class SiteStatusUpdateRequiredVoiceNotification extends Notification
{

    public function __construct(private readonly ProjectNotification $notification) {}

    public function via(object $notifiable): array
    {
        return ['voice'];
    }

    public function toVoice(object $notifiable): TwilioVoice
    {
        $driver = new TwilioVoice;
        $fullPhone = $this->buildInternationalPhoneNumber($notifiable);

        \Log::info('SiteStatusUpdateRequiredVoiceNotification: preparing voice call', [
            'notifiable_id' => $notifiable->id ?? null,
            'notifiable_phone' => $notifiable->phone ?? null,
            'full_phone' => $fullPhone,
            'notification_id' => $this->notification->id,
            'notification_number' => $this->notification->notification_number,
        ]);

        return $driver
            ->to($fullPhone)
            ->twiml($this->buildVoiceTwiml());
    }

    private function buildVoiceTwiml(): string
    {
        $notificationNumber = htmlspecialchars(
            (string) ($this->notification->notification_number ?? ''),
            ENT_XML1,
            'UTF-8'
        );

        $message = "يجب عليك تحديث حالة الموقع للإخطار رقم {$notificationNumber}";
        $encodedMessage = htmlspecialchars($message, ENT_XML1, 'UTF-8');

        return <<<TWIML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Zeina" language="ar-EG">{$encodedMessage}</Say>
    <Pause length="1"/>
    <Say voice="Polly.Zeina" language="ar-EG">{$encodedMessage}</Say>
</Response>
TWIML;
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
