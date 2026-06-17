<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Notifications;

use App\Notifications\Drivers\SMS\MoraSms;
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

        return (new MailMessage)
            ->subject(__('workflow.action_required').': '.$stepName)
            ->markdown('emails.workflowActionRequired', [
                'stepName' => $stepName,
                'stepOrder' => $this->templateStep->step_order,
                'processStepId' => $this->processStep?->id,
            ]);
    }

    public function toSms(object $notifiable)
    {
        $driver = $this->resolveSmsDriver($notifiable);
        $stepName = $this->templateStep->name ?? 'Workflow Step';

        return $driver
            ->to($notifiable->phone)
            ->line(__('workflow.action_required').': '.$stepName);
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
}
