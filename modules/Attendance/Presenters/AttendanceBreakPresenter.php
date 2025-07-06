<?php

declare(strict_types=1);

namespace Modules\Attendance\Presenters;

use BasePackage\Shared\Presenters\AbstractPresenter;
use Modules\Attendance\Models\AttendanceBreak;

class AttendanceBreakPresenter extends AbstractPresenter
{
    public function __construct(private AttendanceBreak $break)
    {
    }

    public function present(bool $isListing = false): array
    {
        return [
            'id' => $this->break->id ? (string)$this->break->id : null,
            'attendance_id' => $this->break->attendance_id ? (string)$this->break->attendance_id : null,
            'company_id' => $this->break->company_id ? (string)$this->break->company_id : null,
            'start_time' => $this->break->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->break->end_time?->format('Y-m-d H:i:s'),
            'duration_minutes' => $this->break->duration_minutes,
            'duration_formatted' => $this->break->getFormattedDuration(),
            'notes' => $this->break->notes,
            'created_at' => $this->break->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->break->updated_at?->format('Y-m-d H:i:s'),
            
            // Computed properties
            'is_active' => $this->break->isActive(),
            'is_completed' => $this->break->isCompleted(),
        ];
    }
}
