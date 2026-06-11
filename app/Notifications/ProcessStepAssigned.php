<?php


namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Modules\Process\Models\ProcessStep;

class ProcessStepAssigned extends Notification
{
    use Queueable;

    protected array $viaChannels;

    public function __construct(
        protected ProcessStep $processStep,
        array $channels = ['mail']
    ) {
        $this->viaChannels = $channels;
    }


    public function via($notifiable): array
    {
        return $this->viaChannels;
    }


    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('لديك مهمة جديدة بانتظار موافقتك')
            ->line('تم تعيينك كمسؤول عن خطوة في نظام المهام.')
            ->line('يرجى مراجعة صندوق الوارد لاتخاذ الإجراء.')
            ->action('عرض المهمة', url('/tasks/' . $this->processStep->process_id));
    }



    public function toSms($notifiable)
    {
        $message = (new \App\Notifications\Drivers\SMS\MoraSms())
            ->to($notifiable->phone)
            ->line('لديك مهمة جديدة بانتظار موافقتك');

        return $message;
    }
    public function toWhatsapp($notifiable): string
    {
        return 'لديك مهمة جديدة بانتظار موافقتك. يرجى مراجعة التطبيق.';
    }
}
