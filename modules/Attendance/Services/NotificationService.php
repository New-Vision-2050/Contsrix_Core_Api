<?php

declare(strict_types=1);

namespace Modules\Attendance\Services;

use Illuminate\Support\Facades\Notification;
use Modules\Attendance\Models\AttendanceViolation;
use Modules\Attendance\Notifications\AttendanceViolationNotification;
use Modules\Company\ManagementHierarchy\Services\ManagementHierarchyService;
use Modules\User\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service for handling attendance-related notifications
 */
class NotificationService
{
    /**
     * @var ManagementHierarchyService
     */
    protected $managementHierarchyService;
    
    /**
     * Constructor
     *
     * @param ManagementHierarchyService $managementHierarchyService
     */
    public function __construct(ManagementHierarchyService $managementHierarchyService)
    {
        $this->managementHierarchyService = $managementHierarchyService;
    }
    
    /**
     * Notify managers about a new attendance violation
     *
     * @param AttendanceViolation $violation The violation record
     * @return bool Success status
     */
    public function notifyViolation(AttendanceViolation $violation): bool
    {
        try {
            // Get the employee from the attendance record
            $employee = $violation->attendance->user;
            
            // Get managers who should be notified based on hierarchy and severity
            $managers = $this->getManagersToNotify($employee, $violation->severity);
            
            if (empty($managers)) {
                Log::info('No managers to notify for violation', [
                    'violation_id' => $violation->id, 
                    'employee_id' => $employee->id
                ]);
                return false;
            }
            
            // Send notification to all managers
            Notification::send($managers, new AttendanceViolationNotification($violation, 'new'));
            
            Log::info('Managers notified about violation', [
                'violation_id' => $violation->id,
                'manager_count' => count($managers),
                'severity' => $violation->severity
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send violation notification', [
                'violation_id' => $violation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Notify managers about an escalated violation
     *
     * @param AttendanceViolation $violation The escalated violation
     * @return bool Success status
     */
    public function notifyEscalatedViolation(AttendanceViolation $violation): bool
    {
        try {
            // Get the employee from the attendance record
            $employee = $violation->attendance->user;
            
            // For escalated violations, notify higher level managers
            $managers = $this->getManagersToNotify($employee, $violation->severity, true);
            
            if (empty($managers)) {
                Log::info('No managers to notify for escalated violation', [
                    'violation_id' => $violation->id, 
                    'employee_id' => $employee->id
                ]);
                return false;
            }
            
            // Send escalation notification
            Notification::send($managers, new AttendanceViolationNotification($violation, 'escalated'));
            
            Log::info('Managers notified about escalated violation', [
                'violation_id' => $violation->id,
                'manager_count' => count($managers),
                'severity' => $violation->severity
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send escalated violation notification', [
                'violation_id' => $violation->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Notify about a resolved violation
     *
     * @param AttendanceViolation $violation The resolved violation
     * @return bool Success status
     */
    public function notifyResolvedViolation(AttendanceViolation $violation): bool
    {
        try {
            // Get the employee from the attendance record
            $employee = $violation->attendance->user;
            
            // Get all managers who were previously notified
            $managers = $this->getManagersToNotify($employee, $violation->severity);
            
            if (empty($managers)) {
                return false;
            }
            
            // Send resolution notification
            Notification::send($managers, new AttendanceViolationNotification($violation, 'resolved'));
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send resolved violation notification', [
                'violation_id' => $violation->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get managers who should be notified based on severity and hierarchy
     *
     * @param User $employee The employee with the violation
     * @param int $severity Violation severity (1-4)
     * @param bool $escalated Whether this is an escalation notification
     * @return array List of manager users
     */
    protected function getManagersToNotify(User $employee, int $severity, bool $escalated = false): array
    {
        // Company ID from the employee
        $companyId = $employee->company_id;
        
        // Get management hierarchy based on severity
        $hierarchyLevel = $this->mapSeverityToHierarchyLevel($severity, $escalated);
        
        // Get managers at the appropriate level
        return $this->managementHierarchyService->getManagersAtLevel(
            $employee->id,
            $companyId,
            $hierarchyLevel
        );
    }
    
    /**
     * Map violation severity to management hierarchy level
     *
     * @param int $severity Violation severity (1-4)
     * @param bool $escalated Whether this is an escalation
     * @return int Management hierarchy level
     */
    protected function mapSeverityToHierarchyLevel(int $severity, bool $escalated): int
    {
        // Default mapping of severity to hierarchy level
        $mapping = [
            1 => 1,  // Low severity: Direct supervisor
            2 => 1,  // Medium severity: Direct supervisor
            3 => 2,  // High severity: Department manager
            4 => 3,  // Critical severity: Senior management
        ];
        
        // For escalated violations, notify one level higher
        if ($escalated) {
            $level = ($mapping[$severity] ?? 1) + 1;
            return min($level, 4); // Cap at level 4 (e.g., C-suite)
        }
        
        return $mapping[$severity] ?? 1;
    }
}
