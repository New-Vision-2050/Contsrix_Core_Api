<?php

namespace Modules\Project\ProjectType\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyTaskAssigned extends Notification
{
    use Queueable;

    public function __construct(private SafetyRecord $record)
    {
        // Queueable already defines $afterCommit. Enable it outside PHPUnit so
        // notifications wait for DB commit; DatabaseTransactions never commits.
        $this->afterCommit = ! app()->runningUnitTests();
    }

    public function via($notifiable): array
    {
        // Database only — realtime delivery is handled by SafetyTaskAssigned Event
        return ['database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'مهمة سلامة جديدة',
            'body' => 'تم تعيين مهمة سلامة لك',
            'safety_record_id' => $this->record->id,
            'project_id' => $this->record->project_id,
            'morphable' => [
                'type' => $this->record->morphable_type,
                'id' => $this->record->morphable_id,
            ],
            'status' => $this->record->status,
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
