<?php

declare(strict_types=1);

namespace Modules\Attendance\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\UuidInterface;

class UpdateAttendance
{
    use Dispatchable, SerializesModels;

    /**
     * The ID of the attendance constraint that was updated.
     * Can be a string UUID or a numeric ID depending on your primary key type.
     *
     * @var string|UuidInterface
     */
    public string $constraintId;

    /**
     * Create a new event instance.
     *
     * @param  string|UuidInterface  $constraintId The ID of the updated constraint.
     * @return void
     */
    public function __construct(string|UuidInterface $constraintId)
    {
        // Ensure the ID is a string for consistent handling, especially if queuing
        if ($constraintId instanceof UuidInterface) {
            $this->constraintId = $constraintId->toString();
        } else {
            $this->constraintId = $constraintId;
        }
    }
}
