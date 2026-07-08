<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix project notification tasks that were auto-rejected by
        //    AutoRejectStaleTaskJob or EmployeeTaskAutoCloseService.
        //    Project notification tasks should NEVER be rejected — they follow
        //    a separate lifecycle and must be manually ended by the employee.
        //
        //    If the task was started (time_from IS NOT NULL) → restore to in_progress.
        //    If the task was never started (time_from IS NULL) → restore to approved.

        DB::table('employee_task_requests')
            ->where('is_project_notification', true)
            ->where('status', 'rejected')
            ->whereNotNull('time_from')
            ->update([
                'status'           => 'in_progress',
                'rejected_at'      => null,
                'rejection_reason' => null,
            ]);

        DB::table('employee_task_requests')
            ->where('is_project_notification', true)
            ->where('status', 'rejected')
            ->whereNull('time_from')
            ->update([
                'status'           => 'approved',
                'rejected_at'      => null,
                'rejection_reason' => null,
            ]);

        // 2. Fix notifications incorrectly set to 'completed' by the previous
        //    migration (2026_07_07_000001_migrate_approved_notifications_to_completed)
        //    which mapped both 'approved' and 'rejected' task statuses to 'completed'.
        //
        //    Correct mapping:
        //      task 'approved'  → notification 'in_progress'
        //      task 'rejected'  → notification 'cancelled' (non-project-notification only)

        DB::table('project_notifications as pn')
            ->join('employee_task_requests as etr', 'pn.employee_task_request_id', '=', 'etr.id')
            ->where('pn.status', 'completed')
            ->where('etr.status', 'approved')
            ->update(['pn.status' => 'in_progress']);

        DB::table('project_notifications as pn')
            ->join('employee_task_requests as etr', 'pn.employee_task_request_id', '=', 'etr.id')
            ->where('pn.status', 'completed')
            ->where('etr.status', 'rejected')
            ->update(['pn.status' => 'cancelled']);

        // 3. Fix notifications set to 'cancelled' where the linked project
        //    notification task was auto-rejected (now restored in step 1).
        //    These should be 'in_progress' since project notifications are never rejected.

        DB::table('project_notifications as pn')
            ->join('employee_task_requests as etr', 'pn.employee_task_request_id', '=', 'etr.id')
            ->where('pn.status', 'cancelled')
            ->where('etr.is_project_notification', true)
            ->whereIn('etr.status', ['in_progress', 'approved'])
            ->update(['pn.status' => 'in_progress']);
    }

    public function down(): void
    {
        // No reliable way to revert this data correction.
    }
};
