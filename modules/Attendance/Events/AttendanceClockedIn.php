<?php

declare(strict_types=1);

namespace Modules\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceClockedIn
{
    use Dispatchable, SerializesModels;

    public $attendanceId;

    /**
     * Create a new event instance.
     *
     * @param $attendanceId
     * @return void
     */
    public function __construct($attendanceId)
    {
        $this->attendanceId = $attendanceId;
    }
}
