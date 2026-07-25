<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Support\Str;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Modules\Project\ProjectType\Notifications\SafetyTaskAssigned as SafetyTaskAssignedNotification;
use Modules\User\Models\User;
use Tests\TestCase;

final class SafetyTaskAssignedNotificationTest extends TestCase
{
    public function test_notification_uses_database_channel_only(): void
    {
        $user = new User();
        $user->forceFill([
            'id' => (string) Str::uuid(),
            'name' => 'Notify User',
        ]);

        $record = new SafetyRecord();
        $record->forceFill([
            'id' => (string) Str::uuid(),
            'project_id' => '9a79b5b5-7e91-11f1-817a-bce92f8cda2e',
            'assigned_user_id' => (string) ($user->getAttributes()['id'] ?? Str::uuid()),
            'status' => 'pending',
            'morphable_type' => 'project_notification',
            'morphable_id' => (string) Str::uuid(),
        ]);

        // Avoid Notification::fake() — User UuidCast breaks NotificationFake array keys.
        $notification = new SafetyTaskAssignedNotification($record);

        $this->assertSame(['database'], $notification->via($user));

        $data = $notification->toDatabase($user);
        $this->assertSame($record->id, $data['safety_record_id']);
        $this->assertSame($record->project_id, $data['project_id']);
        $this->assertSame('pending', $data['status']);
        $this->assertSame('مهمة سلامة جديدة', $data['title']);
    }
}
