<?php

declare(strict_types=1);

namespace Modules\Project\ProjectType\Tests\Unit;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Str;
use Modules\Project\ProjectType\Events\SafetyTaskAssigned;
use Modules\Project\ProjectType\Models\SafetyRecord;
use Tests\TestCase;

final class SafetyTaskAssignedEventTest extends TestCase
{
    public function test_broadcasts_on_private_channel_for_assigned_user(): void
    {
        $userId = (string) Str::uuid();

        $record = new SafetyRecord();
        $record->forceFill([
            'id' => (string) Str::uuid(),
            'assigned_user_id' => $userId,
            'project_id' => (string) Str::uuid(),
            'status' => 'pending',
            'order_type' => 'صيانة',
            'morphable_type' => 'project_notification',
            'morphable_id' => (string) Str::uuid(),
        ]);

        $event = new SafetyTaskAssigned($record);
        $channels = $event->broadcastOn();

        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-safety.notification.' . $userId, $channels[0]->name);
    }

    public function test_broadcast_as_uses_expected_event_name(): void
    {
        $record = new SafetyRecord();
        $record->forceFill(['assigned_user_id' => (string) Str::uuid()]);

        $event = new SafetyTaskAssigned($record);

        $this->assertSame('safety.task.assigned', $event->broadcastAs());
    }

    public function test_broadcast_with_includes_core_payload_fields(): void
    {
        $record = new SafetyRecord();
        $record->forceFill([
            'id' => (string) Str::uuid(),
            'assigned_user_id' => (string) Str::uuid(),
            'project_id' => (string) Str::uuid(),
            'status' => 'pending',
            'order_type' => 'صيانة',
            'time' => '09:30:00',
            'morphable_type' => 'project_notification',
            'morphable_id' => (string) Str::uuid(),
        ]);

        $payload = (new SafetyTaskAssigned($record))->broadcastWith();

        $this->assertSame($record->id, $payload['id']);
        $this->assertSame($record->project_id, $payload['project_id']);
        $this->assertSame('pending', $payload['status']);
        $this->assertSame('09:30', $payload['time']);
        $this->assertSame('project_notification', $payload['morphable']['type']);
        $this->assertSame($record->morphable_id, $payload['morphable']['id']);
        $this->assertSame('مهمة سلامة جديدة', $payload['title']);
    }

    public function test_event_implements_should_broadcast(): void
    {
        $this->assertTrue(is_subclass_of(SafetyTaskAssigned::class, ShouldBroadcast::class));
    }
}
