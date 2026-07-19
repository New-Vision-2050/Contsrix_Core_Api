<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationTypeSiteStatusTypesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_type_site_status_types')) {
            return;
        }

        Schema::create('project_notification_type_site_status_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_notification_type_id');
            $table->uuid('site_status_type_id');
            $table->timestamps();

            $table->foreign('project_notification_type_id', 'pntsst_type_fk')
                ->references('id')
                ->on('project_notification_types')
                ->cascadeOnDelete();

            $table->foreign('site_status_type_id', 'pntsst_site_status_type_fk')
                ->references('id')
                ->on('project_notification_site_status_types')
                ->cascadeOnDelete();

            $table->unique(['project_notification_type_id', 'site_status_type_id'], 'pntsst_pair_uniq');
            $table->index('site_status_type_id', 'pntsst_site_status_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_type_site_status_types');
    }
}
