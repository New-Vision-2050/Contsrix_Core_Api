<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationSiteStatusValuesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_site_status_values')) {
            return;
        }

        Schema::create('project_notification_site_status_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_notification_id');
            $table->uuid('site_status_type_key_id');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(
                ['project_notification_id', 'site_status_type_key_id'],
                'pnsv_notification_key_unique'
            );
            $table->index('project_notification_id', 'pnsv_notification_idx');
            $table->index('site_status_type_key_id', 'pnsv_key_idx');

            $table->foreign('project_notification_id', 'pnsv_notification_fk')
                ->references('id')
                ->on('project_notifications')
                ->cascadeOnDelete();

            $table->foreign('site_status_type_key_id', 'pnsv_key_fk')
                ->references('id')
                ->on('project_notification_site_status_type_keys')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_site_status_values');
    }
}
