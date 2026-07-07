<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_notifications')
            ->whereIn('status', ['approved', 'rejected'])
            ->update(['status' => 'completed']);
    }

    public function down(): void
    {
        // No reliable way to distinguish previously-approved from genuinely-completed.
    }
};
