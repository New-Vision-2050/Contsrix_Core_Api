<?php

declare(strict_types=1);

namespace Modules\Forum\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\NotificationSettings\Services\FirebaseNotificationService;
use Modules\User\Models\User;

class SendForumPushJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param array<int, string> $userIds
     * @param array<string, string> $data FCM data payload (string values only)
     */
    public function __construct(
        public array $userIds,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(): void
    {
        $users = User::query()
            ->whereIn('id', $this->userIds)
            ->whereNotNull('fcm_token')
            ->get(['id', 'fcm_token']);

        foreach ($users as $user) {
            try {
                FirebaseNotificationService::send(
                    $user->fcm_token,
                    $this->title,
                    $this->body,
                    $this->data + ['user_id' => (string) $user->id],
                );
            } catch (\Throwable $e) {
                Log::error('Forum push notification failed', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
