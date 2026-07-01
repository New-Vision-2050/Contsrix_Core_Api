<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Notifications;

use App\Notifications\Drivers\SMS\MoraSms;
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
        $stepName = $this->templateStep->name ?? 'Workflow Step';

        return $driver
            ->to($notifiable->phone)
            ->line(__('emails.workflow-action-required-sms', ['step' => $stepName]));
    }

    public function toWhatsapp(object $notifiable): TwilioWhatsApp
    {
        $driver = $this->resolveWhatsAppDriver($notifiable);
        $stepName = $this->templateStep->name ?? 'Workflow Step';

        $fullPhone = $this->buildInternationalPhoneNumber($notifiable);

        return $driver
            ->to($fullPhone)
            ->line(__('emails.workflow-action-required-sms', ['step' => $stepName]));
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
