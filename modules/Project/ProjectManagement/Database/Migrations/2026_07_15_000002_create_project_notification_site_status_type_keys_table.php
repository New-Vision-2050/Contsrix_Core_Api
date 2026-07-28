<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationSiteStatusTypeKeysTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_site_status_type_keys')) {
            return;
        }

        Schema::create('project_notification_site_status_type_keys', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('site_status_type_id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->string('key')->unique('pnssk_key_unique');
            $table->string('field_type', 30)->default('text');
            $table->json('options')->nullable();
            $table->boolean('show_in_site_status_updates')->default(false);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('site_status_type_id', 'pnssk_type_idx');
            $table->index('key', 'pnssk_key_idx');
            $table->index('sort_order', 'pnssk_sort_idx');
            $table->index('is_active', 'pnssk_active_idx');
            $table->index('show_in_site_status_updates', 'pnssk_show_updates_idx');

            $table->foreign('site_status_type_id', 'pnssk_type_fk')
                ->references('id')
                ->on('project_notification_site_status_types')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_site_status_type_keys');
    }
}
