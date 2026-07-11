<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationReadsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_reads')) {
            return;
        }

        Schema::create('project_notification_reads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_notification_id');
            $table->uuid('user_id');
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->unique(['project_notification_id', 'user_id'], 'pnr_notification_user_unique');
            $table->index(['project_notification_id', 'user_id'], 'pnr_notification_user_idx');
            $table->index('user_id', 'pnr_user_idx');

            $table->foreign('project_notification_id', 'pnr_notification_fk')
                ->references('id')
                ->on('project_notifications')
                ->cascadeOnDelete();

            $table->foreign('user_id', 'pnr_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_reads');
    }
}
