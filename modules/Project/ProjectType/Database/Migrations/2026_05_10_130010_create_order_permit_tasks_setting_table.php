<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_permit_tasks_setting', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->unsignedBigInteger('order_permit_id');
            $table->unsignedBigInteger('order_permit_task_id');
            $table->timestamps();

            $table->foreign('project_type_id', 'op_tasks_setting_ptype_fk')
                ->references('id')
                ->on('project_types')
                ->onDelete('cascade');

            $table->foreign('order_permit_id', 'op_tasks_setting_opermit_fk')
                ->references('id')
                ->on('order_permit')
                ->onDelete('cascade');

            $table->foreign('order_permit_task_id', 'op_tasks_setting_task_fk')
                ->references('id')
                ->on('order_permit_tasks')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_permit_tasks_setting');
    }
};
