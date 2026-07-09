<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationUpdateSiteStatuses extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_update_site_statuses')) {
            return;
        }

        Schema::create('project_notification_update_site_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->string('name_ar');
            $table->string('name_en');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('key');
            $table->index('sort_order');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_update_site_statuses');
    }
}
