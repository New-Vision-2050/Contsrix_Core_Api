<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->uuid('project_notification_id')->nullable()->after('project_id');
            $table->boolean('is_project_notification')->default(false)->after('project_notification_id');
            $table->uuid('sender_user_id')->nullable()->after('is_project_notification');
            $table->string('task_source', 20)->default('mobile')->after('sender_user_id');

            $table->index('project_notification_id', 'etr_notification_idx');
            $table->index('is_project_notification', 'etr_is_notification_idx');
            $table->index('sender_user_id', 'etr_sender_idx');
            $table->index('task_source', 'etr_source_idx');

            $table->foreign('project_notification_id')->references('id')->on('project_notifications')->nullOnDelete();
            $table->foreign('sender_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_task_requests', function (Blueprint $table) {
            $table->dropForeign(['project_notification_id']);
            $table->dropForeign(['sender_user_id']);
            $table->dropIndex('etr_notification_idx');
            $table->dropIndex('etr_is_notification_idx');
            $table->dropIndex('etr_sender_idx');
            $table->dropIndex('etr_source_idx');
            $table->dropColumn([
                'project_notification_id',
                'is_project_notification',
                'sender_user_id',
                'task_source',
            ]);
        });
    }
};
