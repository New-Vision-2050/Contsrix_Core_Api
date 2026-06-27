<?php

declare(strict_types=1);

namespace Modules\ProcedureSetting\Services;

use Illuminate\Support\Facades\Log;
use Modules\NotificationSettings\Services\FirebaseNotificationService;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;
use Modules\User\Models\User;

final class WorkflowPushNotificationService
{
    /**
     * Send FCM push notifications to action takers for a workflow step.
     *
     * @param array<string> $userIds
     */
    public static function sendForStep(ProcedureSettingStep $step, array $userIds): void
    {
        if (! $step->notify_by_push || $userIds === []) {
            return;
        }

        $users = User::query()
            ->whereIn('id', $userIds)
            ->whereNotNull('fcm_token')
            ->get();

        if ($users->isEmpty()) {
            Log::warning('Workflow push notification skipped: no users with FCM tokens', [
                'step_id' => $step->id,
            ]);

            return;
        }

        $title = __('emails.workflow-push-action-required-title');
        $body = __('emails.workflow-push-action-required-body');
        $stepName = $step->name ?? $title;

        foreach ($users as $user) {
            try {
                FirebaseNotificationService::send(
                    $user->fcm_token,
                    $title,
                    $body,
                    [
                        'type' => 'workflow_action_required',
                        'step_id' => (string) $step->id,
                        'step_name' => $stepName,
                        'step_order' => (string) $step->step_order,
                        'user_id' => (string) $user->id,
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Workflow push notification failed', [
                    'user_id' => $user->id,
                    'step_id' => $step->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
