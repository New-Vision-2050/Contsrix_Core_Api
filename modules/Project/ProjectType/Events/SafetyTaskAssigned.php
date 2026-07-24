<?php

namespace Modules\Project\ProjectType\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyTaskAssigned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public SafetyRecord $safetyRecord;

    public function __construct(SafetyRecord $safetyRecord)
    {
        $this->safetyRecord = $safetyRecord;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('safety.notification.' . $this->safetyRecord->assigned_user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'safety.task.assigned';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->safetyRecord->id,
            'title' => 'مهمة سلامة جديدة',
            'body' => 'تم تعيين مهمة سلامة لك',
            'order_type' => $this->safetyRecord->order_type,
            'date' => $this->safetyRecord->date?->toDateString(),
            'time' => $this->safetyRecord->time?->format('H:i'),
            'morphable' => [
                'type' => $this->safetyRecord->morphable_type,
                'id' => $this->safetyRecord->morphable_id,
            ],
            'status' => $this->safetyRecord->status,
            'created_at' => $this->safetyRecord->created_at?->toISOString(),
        ];
    }
}
