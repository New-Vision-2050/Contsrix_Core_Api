<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationNotesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_notes')) {
            return;
        }

        Schema::create('project_notification_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('company_id');
            $table->uuid('project_notification_id');
            $table->uuid('user_id');
            $table->text('note');
            $table->timestamps();

            $table->index(['company_id', 'project_notification_id'], 'pnn_company_notification_idx');
            $table->index('project_notification_id', 'pnn_notification_idx');
            $table->index('user_id', 'pnn_user_idx');

            $table->foreign('company_id', 'pnn_company_fk')
                ->references('id')
                ->on('companies')
                ->cascadeOnDelete();

            $table->foreign('project_notification_id', 'pnn_notification_fk')
                ->references('id')
                ->on('project_notifications')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'pnn_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_notes');
    }
}
