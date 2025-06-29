<?php

namespace Modules\RoleAndPermission\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Modules\User\Models\User;
use Modules\RoleAndPermission\Models\PermissionAuditLog;

class PermissionAuditService
{
    /**
     * Log permission access attempt
     */
    public function logPermissionAccess(
        User $user,
        string $permission,
        bool $granted,
        Request $request = null,
        array $additionalContext = []
    ): void {
        $auditData = [
            'user_id' => $user->id,
            'permission' => $permission,
            'granted' => $granted,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'route' => $request?->route()?->getName(),
            'method' => $request?->method(),
            'url' => $request?->fullUrl(),
            'context' => json_encode($additionalContext),
            'created_at' => now(),
        ];

        // Log to database
        if (config('permissions.audit.database', true)) {
            DB::table('permission_audit_logs')->insert($auditData);
        }

        // Log to file
        if (config('permissions.audit.file', true)) {
            Log::channel('permissions')->info('Permission Access', $auditData);
        }

        // Log critical denials
        if (!$granted && $this->isCriticalPermission($permission)) {
            $this->logCriticalDenial($user, $permission, $request, $additionalContext);
        }
    }

    /**
     * Log critical permission denial
     */
    protected function logCriticalDenial(
        User $user,
        string $permission,
        Request $request = null,
        array $additionalContext = []
    ): void {
        $criticalData = [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'permission' => $permission,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'context' => $additionalContext,
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('security')->warning('Critical Permission Denied', $criticalData);

        // Optionally send notification to administrators
        if (config('permissions.audit.notify_critical_denials', false)) {
            $this->notifyAdministrators('Critical Permission Denied', $criticalData);
        }
    }

    /**
     * Log role assignment changes
     */
    public function logRoleChange(
        User $user,
        string $action,
        string $roleName,
        User $performedBy = null
    ): void {
        $auditData = [
            'user_id' => $user->id,
            'action' => $action, // 'assigned', 'removed'
            'role' => $roleName,
            'performed_by' => $performedBy?->id,
            'created_at' => now(),
        ];

        DB::table('role_change_logs')->insert($auditData);

        Log::channel('permissions')->info('Role Change', $auditData);
    }

    /**
     * Log bulk permission operations
     */
    public function logBulkOperation(
        string $operation,
        array $affectedUsers,
        array $permissions,
        User $performedBy
    ): void {
        $auditData = [
            'operation' => $operation,
            'affected_users' => $affectedUsers,
            'permissions' => $permissions,
            'performed_by' => $performedBy->id,
            'user_count' => count($affectedUsers),
            'permission_count' => count($permissions),
            'timestamp' => now()->toISOString(),
        ];

        Log::channel('permissions')->warning('Bulk Permission Operation', $auditData);
    }

    /**
     * Get permission usage statistics
     */
    public function getPermissionUsageStats(int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stats = DB::table('permission_audit_logs')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('
                permission,
                COUNT(*) as total_attempts,
                COUNT(CASE WHEN granted = 1 THEN 1 END) as granted_attempts,
                COUNT(CASE WHEN granted = 0 THEN 1 END) as denied_attempts,
                COUNT(DISTINCT user_id) as unique_users
            ')
            ->groupBy('permission')
            ->orderBy('total_attempts', 'desc')
            ->get()
            ->toArray();

        return [
            'period' => [
                'start' => $startDate,
                'end' => now(),
                'days' => $days,
            ],
            'stats' => $stats,
            'summary' => $this->calculateUsageSummary($stats),
        ];
    }

    /**
     * Get user permission activity
     */
    public function getUserPermissionActivity(User $user, int $days = 7): array
    {
        $startDate = now()->subDays($days);

        $activity = DB::table('permission_audit_logs')
            ->where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return $item->created_at->format('Y-m-d');
            })
            ->toArray();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'activity' => $activity,
        ];
    }

    /**
     * Get security alerts based on permission patterns
     */
    public function getSecurityAlerts(int $hours = 24): array
    {
        $startDate = now()->subHours($hours);
        $alerts = [];

        // Multiple failed attempts from same IP
        $suspiciousIPs = DB::table('permission_audit_logs')
            ->where('created_at', '>=', $startDate)
            ->where('granted', false)
            ->selectRaw('ip_address, COUNT(*) as failed_attempts')
            ->groupBy('ip_address')
            ->having('failed_attempts', '>=', 10)
            ->get();

        foreach ($suspiciousIPs as $ip) {
            $alerts[] = [
                'type' => 'suspicious_ip',
                'severity' => 'high',
                'message' => "Multiple permission denials from IP {$ip->ip_address}",
                'data' => $ip,
            ];
        }

        // Users attempting critical permissions they don't have
        $criticalAttempts = DB::table('permission_audit_logs')
            ->where('created_at', '>=', $startDate)
            ->where('granted', false)
            ->whereIn('permission', config('permissions.critical_permissions', []))
            ->get();

        foreach ($criticalAttempts as $attempt) {
            $alerts[] = [
                'type' => 'critical_permission_denied',
                'severity' => 'critical',
                'message' => "Critical permission {$attempt->permission} denied to user {$attempt->user_id}",
                'data' => $attempt,
            ];
        }

        return $alerts;
    }

    /**
     * Clean up old audit logs
     */
    public function cleanupOldLogs(int $retentionDays = 90): int
    {
        $cutoffDate = now()->subDays($retentionDays);

        $deletedCount = DB::table('permission_audit_logs')
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned up {$deletedCount} old permission audit logs");

        return $deletedCount;
    }

    /**
     * Export audit logs for compliance
     */
    public function exportAuditLogs(array $filters = []): string
    {
        $query = DB::table('permission_audit_logs')
            ->join('users', 'permission_audit_logs.user_id', '=', 'users.id')
            ->select([
                'permission_audit_logs.*',
                'users.name as user_name',
                'users.email as user_email'
            ]);

        // Apply filters
        if (isset($filters['start_date'])) {
            $query->where('permission_audit_logs.created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('permission_audit_logs.created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['user_id'])) {
            $query->where('permission_audit_logs.user_id', $filters['user_id']);
        }

        if (isset($filters['permission'])) {
            $query->where('permission_audit_logs.permission', 'like', '%' . $filters['permission'] . '%');
        }

        $logs = $query->orderBy('permission_audit_logs.created_at', 'desc')->get();

        // Generate CSV
        $filename = 'permission_audit_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

        $handle = fopen($filepath, 'w');
        
        // Headers
        fputcsv($handle, [
            'ID', 'User ID', 'User Name', 'User Email', 'Permission', 'Granted',
            'IP Address', 'User Agent', 'Route', 'Method', 'URL', 'Context', 'Created At'
        ]);

        // Data
        foreach ($logs as $log) {
            fputcsv($handle, [
                $log->id,
                $log->user_id,
                $log->user_name,
                $log->user_email,
                $log->permission,
                $log->granted ? 'Yes' : 'No',
                $log->ip_address,
                $log->user_agent,
                $log->route,
                $log->method,
                $log->url,
                $log->context,
                $log->created_at,
            ]);
        }

        fclose($handle);

        return $filepath;
    }

    /**
     * Check if permission is critical
     */
    protected function isCriticalPermission(string $permission): bool
    {
        $criticalPermissions = config('permissions.critical_permissions', []);
        return in_array($permission, $criticalPermissions);
    }

    /**
     * Calculate usage summary statistics
     */
    protected function calculateUsageSummary(array $stats): array
    {
        $total = array_sum(array_column($stats, 'total_attempts'));
        $granted = array_sum(array_column($stats, 'granted_attempts'));
        $denied = array_sum(array_column($stats, 'denied_attempts'));

        return [
            'total_attempts' => $total,
            'granted_attempts' => $granted,
            'denied_attempts' => $denied,
            'success_rate' => $total > 0 ? round(($granted / $total) * 100, 2) : 0,
            'most_used_permission' => $stats[0]['permission'] ?? null,
            'total_permissions_used' => count($stats),
        ];
    }

    /**
     * Notify administrators of critical events
     */
    protected function notifyAdministrators(string $subject, array $data): void
    {
        // Implement notification logic (email, Slack, etc.)
        // This is a placeholder for actual notification implementation
        Log::channel('notifications')->critical($subject, $data);
    }
}
