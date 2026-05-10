<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_sharing_tasks_setting');

        Schema::create('project_sharing_tasks_setting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('project_sharing_work_order_id');
            $table->unsignedBigInteger('project_sharing_task_id');
            $table->timestamps();

            $table->foreign('project_type_id', 'pst_tasks_setting_ptype_fk')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');

            $table->foreign('project_sharing_work_order_id', 'pst_tasks_setting_wo_fk')
                ->references('id')
                ->on('project_sharing_work_orders')
                ->onDelete('cascade');

            $table->foreign('project_sharing_task_id', 'pst_tasks_setting_task_fk')
                ->references('id')
                ->on('project_sharing_tasks')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_sharing_tasks_setting');
    }
};
