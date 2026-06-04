<?php

declare(strict_types=1);

namespace Modules\EmployeeTask\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Modules\EmployeeTask\Models\EmployeeTaskRequest;
use Modules\ProcedureSetting\Models\ProcedureSettingStep;


class EmployeeTaskNotification implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;


    public function __construct(
        public EmployeeTaskRequest $task,
        public ProcedureSettingStep $currentStep,
    ) {}


    public function broadcastOn(): array
    {
        $channels = [];

        // Extract channels from currentStep.actionTakers
        foreach ($this->currentStep->actionTakers as $actionTaker) {
            $channels[] = new Channel('inbox.' . $actionTaker->user_id);
        }

        return $channels;
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'employee-task.notification';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        try {
            return [
                'id' => $this->task->id,
                'serial_number' => $this->task->serial_number,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'task_date' => $this->task->task_date?->format('Y-m-d'),
                'duration_hours' => $this->task->duration_hours,
                'description' => $this->task->description,
                'notes' => $this->task->notes,
                'requested_by' => [
                    'id' => $this->task->user_id,
                    'name' => optional($this->task->user)->name ?? 'Unknown',
                ],
                'current_step' => [
                    'id' => $this->currentStep->id,
                    'name' => $this->currentStep->name,
                    'step_order' => $this->currentStep->step_order,
                ],
                'created_at' => $this->task->created_at?->toISOString(),
                'notification_type' => 'employee_task',
            ];
        } catch (\Exception $e) {
            \Log::error('EmployeeTaskNotification broadcast error: ' . $e->getMessage());

            // Return minimal safe data
            return [
                'id' => $this->task->id,
                'serial_number' => $this->task->serial_number,
                'title' => $this->task->title,
                'status' => $this->task->status,
                'notification_type' => 'employee_task',
                'created_at' => now()->toISOString(),
            ];
        }
    }
}
