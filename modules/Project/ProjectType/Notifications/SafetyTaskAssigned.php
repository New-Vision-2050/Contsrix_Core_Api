<?php

namespace Modules\Project\ProjectType\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Modules\Project\ProjectType\Models\SafetyRecord;

class SafetyTaskAssigned extends Notification
{
    use Queueable;

    private SafetyRecord $record;

    public function __construct(SafetyRecord $record)
    {
        $this->record = $record;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'مهمة سلامة جديدة',
            'body' => 'تم تعيين مهمة سلامة لك',
            'safety_record_id' => $this->record->id,
            'morphable' => [
                'type' => $this->record->morphable_type,
                'id' => $this->record->morphable_id,
            ],
        ];
    }
}
