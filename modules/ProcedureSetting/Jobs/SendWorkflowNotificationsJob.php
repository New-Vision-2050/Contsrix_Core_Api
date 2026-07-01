<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\ProcedureSetting\Notifications\WorkflowActionRequired;
use Modules\Process\Models\ProcessStep;
use Modules\User\Models\User;

class SendWorkflowNotificationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    /**
     * @param  array<int, string>  $userIds
     * @param  array<string>  $channels  Delivery channels: 'mail', 'sms', 'whatsapp'
     */
    public function __construct(
        public array $userIds,
        public array $channels,
        public ?string $processStepId = null,
        public ?int $templateStepId = null,
    ) {}

    public function handle(): void
    {
        $templateStep = $this->templateStepId
            ? ProcedureSettingStep::query()->find($this->templateStepId)
            : null;

        if ($templateStep === null) {
            Log::error('SendWorkflowNotificationsJob: template step not found', [
                'template_step_id' => $this->templateStepId,
            ]);

            return;
        }

        $processStep = $this->processStepId
            ? ProcessStep::query()->find($this->processStepId)
            : null;

        $users = User::query()->whereIn('id', $this->userIds)->get();

        foreach ($users as $user) {
            $userChannels = $this->channelsAvailableForUser($this->channels, $user);

            if ($userChannels === []) {
                continue;
            }

            try {
                $user->notify(new WorkflowActionRequired(
                    $processStep,
                    $templateStep,
                    $userChannels,
                ));
            } catch (\Throwable $e) {
                Log::error('SendWorkflowNotificationsJob: notification failed', [
                    'user_id' => $user->id,
                    'channels' => $userChannels,
                    'process_step_id' => $this->processStepId,
                    'template_step_id' => $this->templateStepId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function channelsAvailableForUser(array $channels, User $user): array
    {
        $available = $channels;

        if (in_array('mail', $available, true) && trim((string) $user->email) === '') {
            Log::warning('SendWorkflowNotificationsJob: mail skipped, user has no email', [
                'user_id' => $user->id,
            ]);
            $available = array_values(array_diff($available, ['mail']));
        }

        if (in_array('sms', $available, true) && trim((string) $user->phone) === '') {
            Log::warning('SendWorkflowNotificationsJob: sms skipped, user has no phone', [
                'user_id' => $user->id,
            ]);
            $available = array_values(array_diff($available, ['sms']));
        }

        if (in_array('whatsapp', $available, true)) {
            $phone = trim((string) $user->phone);
            $phoneCode = trim((string) ($user->phone_code ?? ''));
            if ($phone === '' || ($phoneCode === '' && ! str_starts_with($phone, '+'))) {
                Log::warning('SendWorkflowNotificationsJob: whatsapp skipped, user has no phone or phone_code', [
                    'user_id' => $user->id,
                    'has_phone' => $phone !== '',
                    'has_phone_code' => $phoneCode !== '',
                ]);
                $available = array_values(array_diff($available, ['whatsapp']));
            }
        }

        return $available;
    }
}
