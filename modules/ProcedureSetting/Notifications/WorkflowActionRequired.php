<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Notifications;

use App\Notifications\Drivers\SMS\MoraSms;
use App\Notifications\Drivers\Voice\TwilioVoice;
use App\Notifications\Drivers\WhatsApp\TwilioWhatsApp;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Country\Models\Country;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\Process\Models\ProcessStep;

class WorkflowActionRequired extends Notification
{
    use Queueable;

    /**
     * @param  array<string>  $channels  Delivery channels: 'mail', 'sms'
     */
    public function __construct(
        public ?ProcessStep $processStep,
        public ProcedureSettingStep $templateStep,
        public array $channels = ['mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $stepName = $this->templateStep->name ?? 'Workflow Step';
        $data = [
            'name' => $notifiable->name ?? '',
            'step_name' => $stepName,
            'step_order' => $this->templateStep->step_order,
            'process_step_id' => $this->processStep?->id,
        ];

        return (new MailMessage)
            ->subject(__('emails.workflow-action-required-subject').': '.$stepName)
            ->view('emails.workflowActionRequired', [
                'data' => $data,
            ]);
    }

    public function toSms(object $notifiable)
    {
        $driver = $this->resolveSmsDriver($notifiable);

        return $driver
            ->to($notifiable->phone)
            ->line($this->buildActionMessage());
    }

    public function toWhatsapp(object $notifiable): TwilioWhatsApp
    {
        $driver = $this->resolveWhatsAppDriver($notifiable);

        $fullPhone = $this->buildInternationalPhoneNumber($notifiable);

        return $driver
            ->to($fullPhone)
            ->line($this->buildActionMessage());
    }

    public function toVoice(object $notifiable): TwilioVoice
    {
        $driver = new TwilioVoice;
        $fullPhone = $this->buildInternationalPhoneNumber($notifiable);

        return $driver
            ->to($fullPhone)
            ->twiml($this->buildVoiceTwiml());
    }

    private function buildActionMessage(): string
    {
        $stepName = $this->templateStep->name ?? 'Workflow Step';

        return __('emails.workflow-action-required-detailed', [
            'step' => $stepName,
            'order' => $this->templateStep->step_order ?? 1,
        ]);
    }

    private function buildVoiceMessage(): string
    {
        $stepName = $this->templateStep->name ?? 'Workflow Step';

        return trans('emails.workflow-action-required-detailed', [
            'step' => $stepName,
            'order' => $this->templateStep->step_order ?? 1,
        ], 'en');
    }

    private function buildVoiceTwiml(): string
    {
        $message = htmlspecialchars($this->buildVoiceMessage(), ENT_XML1, 'UTF-8');

        return <<<TWIML
<?xml version="1.0" encoding="UTF-8"?>
<Response>
    <Say voice="Polly.Joanna" language="en-US">$message</Say>
    <Pause length="1"/>
    <Say voice="Polly.Joanna" language="en-US">$message</Say>
</Response>
TWIML;
    }

    private function resolveSmsDriver(object $notifiable): MoraSms
    {
        if (! property_exists($notifiable, 'phone_code') || ! $notifiable->phone_code) {
            return new MoraSms;
        }

        $country = Country::query()
            ->where('phonecode', str_replace('+', '', $notifiable->phone_code))
            ->first();

        if ($country && $country->smsDriver && $country->smsDriver->name === 'mora') {
            return new MoraSms;
        }

        return new MoraSms;
    }

    private function resolveWhatsAppDriver(object $notifiable): TwilioWhatsApp
    {
        return new TwilioWhatsApp;
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
