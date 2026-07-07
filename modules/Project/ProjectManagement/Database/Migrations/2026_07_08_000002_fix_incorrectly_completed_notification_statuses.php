<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix notifications incorrectly set to 'completed' by the previous
        // migration (2026_07_07_000001_migrate_approved_notifications_to_completed)
        // which mapped both 'approved' and 'rejected' task statuses to 'completed'.
        //
        // The correct mapping is:
        //   task 'approved'  → notification 'in_progress' (displays as "received")
        //   task 'rejected'  → notification 'cancelled'
        //   task 'completed' → notification 'completed' (genuine)

        DB::table('project_notifications as pn')
            ->join('employee_task_requests as etr', 'pn.employee_task_request_id', '=', 'etr.id')
            ->where('pn.status', 'completed')
            ->whereIn('etr.status', ['approved', 'rejected'])
            ->update([
                'pn.status' => DB::raw("CASE WHEN etr.status = 'approved' THEN 'in_progress' ELSE 'cancelled' END"),
            ]);
    }

    public function down(): void
    {
        // No reliable way to revert this data correction.
    }
};
