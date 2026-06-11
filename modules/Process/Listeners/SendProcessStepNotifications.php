<?php
declare(strict_types=1);
namespace Modules\Process\Listeners;

use Modules\Process\Events\ProcessStepPending;
use Modules\Process\Models\ProcessStepActionTaker;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ProcessStepAssigned;

class SendProcessStepNotifications
{
    public function handle(ProcessStepPending $event): void
    {
        $step      = $event->processStep;
        $mailClass = new \App\Http\Controllers\HelperClass\MailClass();
        $mailClass->setConfig();

        $actionTakers = ProcessStepActionTaker::where('process_step_id', $step->id)
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter();

        $channels = [];
        if ($step->notify_by_sms)    $channels[] = 'sms';
        if ($step->notify_by_email)  $channels[] = 'mail';
        if ($step->notify_by_whatsapp) $channels[] = 'whatsapp';

        if (!empty($channels)) {
            Notification::send($actionTakers, new ProcessStepAssigned($step, $channels));
        }
    }
}
