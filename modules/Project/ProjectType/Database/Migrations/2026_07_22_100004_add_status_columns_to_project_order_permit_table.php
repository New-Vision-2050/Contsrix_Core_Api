<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->foreignId('project_completion_phase_id')->nullable()->constrained('project_completion_phases')->onDelete('set null')->after('state_id');
            $table->foreignId('project_phase_status_id')->nullable()->constrained('project_phase_statuses')->onDelete('set null')->after('project_completion_phase_id');
            $table->foreignId('connection_completion_phase_id')->nullable()->constrained('connection_completion_phases')->onDelete('set null')->after('project_phase_status_id');
            $table->foreignId('connection_phase_status_id')->nullable()->constrained('connection_phase_statuses')->onDelete('set null')->after('connection_completion_phase_id');
        });
    }

    public function down(): void
    {
        Schema::table('project_order_permit', function (Blueprint $table) {
            $table->dropForeign(['project_completion_phase_id']);
            $table->dropColumn('project_completion_phase_id');
            $table->dropForeign(['project_phase_status_id']);
            $table->dropColumn('project_phase_status_id');
            $table->dropForeign(['connection_completion_phase_id']);
            $table->dropColumn('connection_completion_phase_id');
            $table->dropForeign(['connection_phase_status_id']);
            $table->dropColumn('connection_phase_status_id');
        });
    }
};
