<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectNotificationFineItems extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_notification_fine_items')) {
            return;
        }

        Schema::create('project_notification_fine_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_notification_fine_id');
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('project_notification_fine_id', 'pnfi_fine_idx');
            $table->index('sort_order', 'pnfi_sort_idx');

            $table->foreign('project_notification_fine_id', 'pnfi_fine_fk')
                ->references('id')
                ->on('project_notification_fines')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_notification_fine_items');
    }
}
