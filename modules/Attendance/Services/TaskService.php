<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Carbon\Carbon;
use Modules\Attendance\Models\AttendanceTask;
use Modules\Attendance\Models\AttendanceConstraint;
use Modules\Attendance\Models\Attendance;
use Modules\User\Models\User;
use Ramsey\Uuid\Uuid;

/**
 * Service class for handling attendance-related tasks
 */
class TaskService
{
    /**
     * Create a task for handling exceptions and violations
     *
     * @param string $userId The user ID the task is related to
     * @param string $constraintId The constraint ID related to this task
     * @param string $type The task type (e.g., 'exception_handling', 'violation_report')
     * @param array $details Additional task details specific to the task type
     * @param string|null $assignedTo User ID of the person assigned to the task (optional)
     * @param string|null $dueDate Due date for the task (optional)
     * @param string $priority Priority level ('low', 'medium', 'high', 'critical')
     * @return AttendanceTask The created task
     */
    public function createTask(
        string $userId, 
        string $constraintId, 
        string $type, 
        array $details, 
        ?string $assignedTo = null,
        ?string $dueDate = null,
        string $priority = 'medium'
    ): AttendanceTask {
        // Default due date is 24 hours from now if not specified
        $dueDateObject = $dueDate ? Carbon::parse($dueDate) : Carbon::now()->addDay();
        
        $task = new AttendanceTask();
        $task->id = (string) Uuid::uuid4();
        $task->user_id = $userId;
        $task->constraint_id = $constraintId;
        $task->type = $type;
        $task->details = $details;
        $task->status = AttendanceTask::STATUS_PENDING;
        $task->assigned_to = $assignedTo;
        $task->assigned_at = $assignedTo ? Carbon::now() : null;
        $task->due_date = $dueDateObject;
        $task->priority = $priority;
        $task->created_at = Carbon::now();
        $task->save();
        
        return $task;
    }
    
    /**
     * Create a task for handling constraint exception
     *
     * @param Attendance $attendance The attendance record with the exception
     * @param AttendanceConstraint $constraint The constraint that was violated
     * @param array $violationDetails Details about the constraint violation
     * @return AttendanceTask
     */
    public function createConstraintExceptionTask(
        Attendance $attendance, 
        AttendanceConstraint $constraint, 
        array $violationDetails
    ): AttendanceTask {
        // Find the appropriate supervisor or manager to assign the task to
        $assignee = $this->findAppropriateTaskAssignee($attendance->user_id, $constraint);
        
        $details = [
            'attendance_id' => $attendance->id,
            'violation_timestamp' => Carbon::now()->toDateTimeString(),
            'violation_type' => $constraint->constraint_type,
            'violation_subtype' => $constraint->subtype ?? null,
            'violation_details' => $violationDetails,
            'resolution_options' => [
                'approve_exception' => true,
                'reject_exception' => true,
                'create_temporary_exception' => true
            ]
        ];
        
        return $this->createTask(
            $attendance->user_id,
            $constraint->id,
            'constraint_exception',
            $details,
            $assignee?->id,
            Carbon::now()->addHours(24)->toDateTimeString(),
            $this->determinePriority($constraint)
        );
    }
    
    /**
     * Find the appropriate person to assign the task to based on hierarchy
     * 
     * @param string $userId The user ID who has the violation/exception
     * @param AttendanceConstraint $constraint The constraint being violated
     * @return User|null The user to assign the task to
     */
    private function findAppropriateTaskAssignee(string $userId, AttendanceConstraint $constraint): ?User
    {
        // First try to find direct supervisor
        // This is a placeholder - in a real implementation, you'd query the user's supervisor
        // from your management hierarchy system
        $user = User::find($userId);
        if ($user && $user->supervisor_id) {
            return User::find($user->supervisor_id);
        }
        
        // If no direct supervisor, assign to branch manager if applicable
        if ($constraint->branch_ids && is_array($constraint->branch_ids) && count($constraint->branch_ids) > 0) {
            $branchId = $constraint->branch_ids[0]; // Get first branch
            
            // This is a placeholder - in a real implementation, you'd query the branch manager
            // Get branch manager (placeholder implementation)
            // $branchManager = BranchManager::where('branch_id', $branchId)->first();
            // if ($branchManager) {
            //    return User::find($branchManager->user_id);
            // }
        }
        
        // If no branch manager, assign to HR or attendance admin
        // This is a placeholder - in a real implementation, you'd have a method to find
        // users with the appropriate role
        
        // Fallback: Return null, system will need to manually assign
        return null;
    }
    
    /**
     * Determine task priority based on constraint type and configuration
     * 
     * @param AttendanceConstraint $constraint
     * @return string Priority level ('low', 'medium', 'high', 'critical')
     */
    private function determinePriority(AttendanceConstraint $constraint): string
    {
        // If constraint config has a violation_severity field, use that
        if (isset($constraint->config['violation_severity'])) {
            $severity = $constraint->config['violation_severity'];
            
            switch ($severity) {
                case 'low':
                    return 'low';
                case 'medium':
                    return 'medium';
                case 'high':
                    return 'high';
                case 'critical':
                    return 'critical';
                default:
                    return 'medium';
            }
        }
        
        // Default priorities based on constraint type
        switch ($constraint->constraint_type) {
            case AttendanceConstraint::TYPE_SECURITY:
                return 'high';
            case AttendanceConstraint::TYPE_COMPLIANCE:
                return 'high';
            case AttendanceConstraint::TYPE_LOCATION:
                return 'medium';
            case AttendanceConstraint::TYPE_TIME:
                return 'medium';
            default:
                return 'medium';
        }
    }
}
