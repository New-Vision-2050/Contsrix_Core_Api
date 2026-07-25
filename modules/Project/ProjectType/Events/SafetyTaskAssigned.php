<?php

namespace Modules\Project\ProjectType\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyTaskAssigned implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public function __construct(public SafetyRecord $safetyRecord) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('safety.notification.' . $this->safetyRecord->assigned_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'safety.task.assigned';
    }

    public function broadcastWith(): array
    {
        $time = $this->safetyRecord->time;
        if (is_string($time) && strlen($time) >= 5) {
            $time = substr($time, 0, 5);
        }

        return [
            'id' => $this->safetyRecord->id,
            'title' => 'مهمة سلامة جديدة',
            'body' => 'تم تعيين مهمة سلامة لك',
            'project_id' => $this->safetyRecord->project_id,
            'order_type' => $this->safetyRecord->order_type,
            'date' => $this->safetyRecord->date?->toDateString(),
            'time' => $time,
            'morphable' => [
                'type' => $this->safetyRecord->morphable_type,
                'id' => $this->safetyRecord->morphable_id,
            ],
            'status' => $this->safetyRecord->status,
            'created_at' => $this->safetyRecord->created_at?->toISOString(),
        ];
    }
}
